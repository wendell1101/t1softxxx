<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * MGW Seamless Integration
 * OGP-30051
 * ? uses mgw_seamless_game_service_api for its service API
 *
 * Game Platform ID: 6319
 *
 */

abstract class Abstract_game_api_common_mgw_seamless extends Abstract_game_api {

	const POST  = 'POST';
	const GET   = 'GET';
	const PUT   = 'PUT';

    public  $site_id, $mw_public_key, $firm_id , $merchant_id,$ec_public_key, $ec_private_key ,
            $currency ,$api_url, $api_domain, $language, $use_monthly_transactions_table ,$URI_MAP ,
            $method ,$trans_records,$backend_api_white_ip_list,
            $tester_white_ip_list, $manual_request;
    public function __construct() {
        parent::__construct();
        $this->CI->load->model(array('wallet_model','game_provider_auth','common_token','player_model', 'ip','game_logs','external_common_tokens'));
        $this->trans_records =  [];
        
        #$this->site_id  = "300000039";#
        #$this->firm_id = '300000213';#
        #$this->merchant_id = '30000097';
        #$this->ec_public_key = "-----BEGIN PUBLIC KEY-----\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDR5vE9km2Dp09XE0ssIRhqlJ42\nJZ6LoI+4ohjcazwhSHfduAox6UZhcffsEFjB2Hthj/Ntt03KUeJWcJp6e6wNM203\nAaRoJRqQu+ZjUgzfgCZlsasYB5KEJrh+JtJxQb3ygmCAzEtMrHWATuCcti0EgeSA\nH/sgiPKBFBSChVumVQIDAQAB\n-----END PUBLIC KEY-----";
        #$this->ec_private_key = "-----BEGIN RSA PRIVATE KEY-----\nMIICXgIBAAKBgQDR5vE9km2Dp09XE0ssIRhqlJ42JZ6LoI+4ohjcazwhSHfduAox\n6UZhcffsEFjB2Hthj/Ntt03KUeJWcJp6e6wNM203AaRoJRqQu+ZjUgzfgCZlsasY\nB5KEJrh+JtJxQb3ygmCAzEtMrHWATuCcti0EgeSAH/sgiPKBFBSChVumVQIDAQAB\nAoGBALuFW54LVAVbEpmTJgRNqNeG4HU1VJgfIGbtgdJhhv2hFV0iTxFZ+0ORItFl\npTXApjF5/hrVuQx37QIWZRvposEl14XCEyR5UxxxIaC+Ap8IYFhYst4puL3ZnAk/\nlrlmPAAta6n5a1hJmxSLsS5TS32bFRDCwPhQJiwCUdeiPfWdAkEA8YrnKwJlCDLe\nTxLH6mqY8YvYZ9aw0rzFJ6Uq8pzoFy5phayc3NWAqE8f2kgvCaH/Y3g2hgsIyYV8\n7TdNioVFqwJBAN53OGQVwq3ZB1B2ZYrwgarotbqEzvhBdBP6/2C4aAYKZL8sxcYw\nBxnKrZKUBV0h2Lkp9FTZxFf1oIHEXhWew/8CQA9bnJ0wdsoRqe7vK8Ts6DKbiLP5\ng56yn/qIVvW8IkmvCsiUFBk6fga1mTng0xTStxFVCGp3cySVFz9h/80p8icCQQCE\n1XMab9Pasmgnp0pid9E1F9bLFFnw6kRBWfH68qFKWhJmBHnjKPJUeCzEBRZe0cLy\nbRazQ4R1cPjyAyqahj5JAkEA5xKqC3g5l3Eq38W1kCZK9frY5ZvqKqMdDLopLaul\nbe1lut5s6u+Lz60fysKlcOACSpbP5K8nBiKwxhAS5ZgGDQ==\n-----END RSA PRIVATE KEY-----";
        #$this->mw_public_key = "-----BEGIN PUBLIC KEY-----\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCymTFlGFBsxZJE6MbWgMiSm5S6\n8VWWSRcn6AGVVDBMWntEFFHP9WFCilBMjQmpsOE8Fv2LXRS4S2GGqpcFBiZl8/26\npkIy5GH51NsHNxAW7OK++QNkb9wquApSIWKE3ggbUPq/Kyzncpb9In5TpY+75bDj\nuzANDgc02Kmk9FUjkQIDAQAB\n-----END PUBLIC KEY-----";

        $this->site_id                          = $this->getSystemInfo('site_id');
        $this->firm_id                          = $this->getSystemInfo('firm_id');
        $this->merchant_id                      = $this->getSystemInfo('merchant_id');
        $this->ec_public_key                    = $this->getSystemInfo('ec_public_key');
        $this->ec_private_key                   = $this->getSystemInfo('ec_private_key');
        $this->mw_public_key                    = $this->getSystemInfo('mw_public_key');
		$this->api_url                          = $this->getSystemInfo('url');
        $this->api_domain                       = $this->generateDomain();
        $this->currency                         = $this->getSystemInfo('currency');
        $this->language                         = $this->getSystemInfo('language', 'en');
        $this->manual_request                   = $this->getSystemInfo('manual_request', false);

        $this->utils->debug_log("MGW-extra",  $this->site_id, $this->firm_id, $this->merchant_id, $this->ec_public_key, $this->ec_private_key, $this->mw_public_key);
        #http://www.168at168.com/as-lobby/api/nmw/common/Domain

        $this->URI_MAP = array(
            self::API_queryForwardGame => 'api/nmw/common/Oauth',
        );

    }


    const BET_TYPE_REAL = 'Real game';
    const BET_TYPE_REAL_CODE = 1;

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';
    const TRANSACTION_ROLLBACK = 'rollback';

    const MD5_FIELDS_FOR_MERGE = [
        'status',
        'updated_at'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'win_gold',
    ];

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode() {
        return MGW_SEAMLESS_GAME_API;
    }

    public function getCurrency() {
        return isset($this->currency) ? $this->currency : 'BRL';
    }

    public function generateUrl($apiName, $params) {
		$apiUri = $this->URI_MAP[$apiName];
        $url = $this->api_domain . $apiUri;
		return $url;
	}

	protected function customHttpCall($ch, $params) {
		if($this->method == self::POST){
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($params));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		}
	}

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null){
        $this->utils->debug_log("MGW SEAMLESS: (createPlayer)");

        // create player on game provider auth
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
        $success = false;
        $message = "Unable to create account for MGW seamless api";
        if($return){
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = "Successfull create account for MGW seamless api";
        }
        
        return array("success" => $success, "message" => $message);
    }

    public function isPlayerExist($playerName) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerBalance($playerName = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );
        $this->method = self::POST;
        $this->utils->debug_log('MGW-queryPlayerBalance', $result);

        return $result;
    }

    public function depositToGame($userName, $amount, $transfer_secure_id = null){
        return array(
            'success' => true,
            'external_transaction_id' => $transfer_secure_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id = null){
        return array(
            'success' => true,
            'external_transaction_id' => $transfer_secure_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    public function getLauncherLanguage($language){
        $lang='';
        $language = strtolower($language);
        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en-us':
                $lang = 'en'; // english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'zh-cn':
                $lang = 'cn'; // chinese
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id-id':
                $lang = 'in';
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko-kr':
                $lang = 'kr';
                break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt-br':
            case 'pt-pt':
            case 'pt':
                $lang = 'pt';
                break;
            case Language_function::INT_LANG_THAI:
            case 'th-th':
                $lang = 'th';
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vt-vt':
                $lang = 'vt';
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

    public function send_api($func, $api_url, $key, $data) {

        if ($func == "SiteUserGameLog") {
            $api_url = str_replace("as-lobby", "as-service", $api_url);
        }
        $data_map = array(
            'data' => $data,
            'key' => $key,
        );
        try {
            $req = $this->post_request($func, $api_url, $data_map);
        } catch (Exception $err) {
            return null;
        }

        return $req;
    }
    
    public function post_request($func, $apiUrl, $data) {
        try {
            $headers = array(
                'Content-Type: application/json',
                'method: ' . $func,
                'siteId: ' . $this->site_id,
            );
            
            $jsonData = json_encode($data);
            
            $ch = curl_init();
            $options = array(
                CURLOPT_URL=>$apiUrl,
                CURLOPT_SSL_VERIFYPEER=>false,
                CURLOPT_POST=>true,
                CURLOPT_HTTPHEADER=>$headers,
                CURLOPT_POSTFIELDS=>$jsonData,
                CURLOPT_RETURNTRANSFER =>true,
            );
            
            curl_setopt_array($ch, $options);
            $response = curl_exec($ch);
            curl_close($ch);
            
            return $response;
        } catch (Exception $err) {
            return null;
        }    
    }

    public function rsa_encrypt($aesKey, $pubkey) {
        openssl_public_encrypt($aesKey, $encryptKey, $pubkey);
        $encryptKey = base64_encode($encryptKey);
        return $encryptKey;
    }
    
    public function rsa_decrypt($ciphertext, $prikey) {
        $receiveKey = base64_decode($ciphertext);
        $ecPrivateKey = openssl_pkey_get_private($prikey);
        openssl_private_decrypt($receiveKey, $decryptAesKey, $ecPrivateKey);
        return $decryptAesKey;
    }

    public function aes_encrypt($key, $str) {
        $encrypted = openssl_encrypt($str, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        $data = base64_encode($encrypted);
        return $data;
    }
    
    public function aes_decrypt($key, $str) {
        $encryptedData = base64_decode($str);
        $decrypted = openssl_decrypt($encryptedData, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        return $decrypted;
    }
       
    public function encrypt_key_data($data) {
        $aesKey = $this->generate_aes_key(); // 生成AESKEY 長度16
        $encryptKey = $this->rsa_encrypt($aesKey, $this->mw_public_key); // MW平台公鑰 加密 AESKEY 獲得 EC加密KEY
        $jsonStr = json_encode($data); // DATA 轉成 JSON字串
        $encryptData = $this->aes_encrypt($aesKey, $jsonStr); // AESKEY 加密 JSON字串 獲得 EC加密DATA
        return [$encryptKey, $encryptData]; // 發送 EC加密KEY、EC加密DATA 給 MW
    }

    public function generate_aes_key() {
        $aes = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol) - 1;
        for ($i = 0; $i < 16; $i++) {
            $aes .= $strPol[rand(0, $max)];
        }
        return $aes;
    }
    
    public function generateDomain() {
        $data = array(
            'timestamp' => time()
        );
        #$apiUrl = "http://www.168at168.com/as-lobby/api/nmw/common/Domain";
        $apiUrl = $this->api_url;
        $keyData = $this->encrypt_key_data($data);
        try {
            $response = $this->send_api("Domain", $apiUrl, $keyData[0], $keyData[1]);
            $response = $this->decrypt_response($response);
            return $response["domain"];
        } catch (Exception $err) {
            return null;
        }	
    }

    public function decrypt_response($response) {
        $response_json = json_decode($response, true);
        $encryptKey = $response_json["key"]; // MW加密KEY
        $encryptData = $response_json["data"]; // MW加密DATA
        $aesKey = $this->rsa_decrypt($encryptKey, $this->ec_private_key); // EC方私鑰 解密 MW加密KEY 獲得 AESKEY
        $decryptData = $this->aes_decrypt($aesKey, $encryptData); // AESKEY 解密 MW加密DATA 獲得 DATA
        return json_decode($decryptData, true);

    }

    public function queryForwardGame($playerName, $extra=null){
        $this->CI->load->model(['external_common_tokens']);
        $this->utils->debug_log("MGW Seamless: (queryForwardGame)", $playerName, $extra);   
        if(isset($extra['language']) && !empty($extra['language'])){
            $language=$this->getLauncherLanguage($extra['language']);
        }else{
            $language=$this->getLauncherLanguage($this->language);
        }
        $gameUsername            = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId                = $this->getPlayerIdByGameUsername($gameUsername);
        $existingToken           = $this->getExternalAccountIdByPlayerUsername($playerName);
        $token                   = $this->getPlayerToken($playerId);
        if($existingToken){
            $token = $existingToken;
        }else{
            $this->updateExternalAccountIdForPlayer($playerId, $token);
        }
        $params = array(
            'uid'       => $gameUsername,    
            'utoken'    => $token,
            'firmId'    => $this->firm_id,
            'jumpType'  => '0',
            'currency'  => $this->currency,
            'lang'      => $language,
            'isssl'     => '0'
        );
        if(isset($extra['game_code']) || $extra['game_code'] !== ""){
            $params['gameId'] = $extra['game_code'];
        }

        if(!$extra['game_code']){
            unset($params['gameId']);
        }
        
        $keyData = $this->encrypt_key_data($params);
        $apiURL = $this->generateDomain() . $this->URI_MAP[self::API_queryForwardGame];
        try {
            $response = $this->send_api("Oauth", $apiURL, $keyData[0], $keyData[1]);
            $response = $this->decrypt_response($response);

            $this->utils->debug_log('MGW-gamelaunch', $response , $params);
            
            $returnResponse = [ 
                "success" => true,
                "url"     => isset($response['interface2']) ? $response['interface2'] : null,
                "message" => isset($response['msg']) ? $response['msg'] : null,
            ];
            if(isset($response['ret'])){
                $returnResponse['error'] = $response['ret'];
            }
            return $returnResponse;
        } catch (Exception $err) {
            return null;
        }
    }

    public function getGameUsernameByPlayerUsername($playerUsername) {
        if (!empty($playerUsername)) {
            $this->CI->load->model('game_provider_auth');
            $gameUsername = $this->CI->game_provider_auth->getGameUsernameByPlayerUsername($playerUsername, $this->getPlatformCode());
            if (empty($gameUsername)) {
                $this->CI->load->model('player_model');
                $player = $this->CI->player_model->getPlayerByUsername($playerUsername);
                if (!empty($player)) {
                    // $gameUsername = $this->convertUsernameToGame($player->username);
                    $this->CI->load->library('salt');
                    $decryptedPwd = $this->CI->salt->decrypt($player->password, $this->getConfig('DESKEY_OG'));

                    $this->createPlayerInDB($playerUsername, $player->playerId, $decryptedPwd);
                    $gameUsername = $this->CI->game_provider_auth->getGameUsernameByPlayerUsername($playerUsername, $this->getPlatformCode());
                }
            }

            return $gameUsername;
        }
        return null;
    }


    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token = false) {
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

    /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        $original_transactions_table = $this->getTransactionsTable();

        $currentTableData = $this->queryOriginalGameLogsWithTable($original_transactions_table, $dateFrom, $dateTo, $use_bet_time);        

        $this->CI->utils->debug_log("MGW SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', $original_transactions_table);
        $prevTableData = [];

        $checkOtherTable = $this->checkOtherTransactionTable();

        if($checkOtherTable||$this->force_check_other_transaction_table){            
            $prevTable = $this->getTransactionsPreviousTable();   
            $this->CI->utils->debug_log("MGW SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', 'prevTable', $prevTable);
            $prevTableData = $this->queryOriginalGameLogsWithTable($prevTable, $dateFrom, $dateTo, $use_bet_time);                               
        }

        $gameRecords = array_merge($currentTableData, $prevTableData);        
        //$this->processGameRecordsFromTrans($gameRecords);
        return $gameRecords;
    }

    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $this->utils->debug_log('MGW-syncOrig', $table, $dateFrom, $dateTo);           
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

      
        $this->CI->utils->debug_log('MGW SEAMLESS GAME sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.gold', 'original.after_balance', 'original.win_gold', 'original.updated_at'));
        //result amount = win - bet
        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.player_id,
    original.merchant_id,
    original.site_id,
    original.firm_id,
    original.game_id,
    original.game_class,
    original.risk,
    original.game_round_id as round_id,
    original.wager_id as wager_id,
    original.gold as bet_amount,
    original.vgold as real_bet_amount,
    original.win_gold as win_amount,
    original.unused_gold,
    original.commission_gold,
    original.action,
    original.result as bet_result,
    original.order_date,
    original.add_date,
    original.result_date,
    original.freegame,
    original.utoken,
    original.uid as player_name,
    original.ip,
    original.device,
    original.currency,
    original.trans_type,
    original.trans_status as status,
    original.balance_adjustment_amount,
    original.balance_adjustment_method,
    original.after_balance,
    original.before_balance,
    original.response_result_id,
    original.external_uniqueid,
    original.game_platform_id,
    original.elapsed_time,
    original.created_at,
    original.updated_at,
    

    MD5(CONCAT({$md5Fields})) as md5_sum,
    gd.game_code as game_code,
    gd.game_name as game_name,
    gd.id as game_description_id,
    gd.game_name as game_description_name,
    gd.game_type_id
FROM {$table} as original
LEFT JOIN game_description as gd ON original.game_id = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
WHERE (original.action='placebet') AND
{$sqlTime};
EOD;

        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];

		$this->CI->utils->debug_log('MGW-syncSQL', $sql, 'params',$params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $this->CI->utils->debug_log('MGW SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row', $row);
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        $row['updated_at'] = date('Y-m-d H:i:s', ($row['updated_at']/1000));
        $this->CI->utils->debug_log('MGW SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row[updated_at]', $row['updated_at']);


        $resultAmount = ($row['win_amount'] ? $row['win_amount'] : 0) - ABS($row['bet_amount']);

        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => null,
                'game'                  => $row['game_description_name']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_name']
            ],
            'amount_info' => [
                'bet_amount'            => $row['bet_amount'],
                'result_amount'         => $resultAmount,
                'bet_for_cashback'      => $row['bet_amount'],
                'real_betting_amount'   => $row['real_bet_amount'] ? $row['real_bet_amount'] : 0,
                'win_amount'            => $row['win_amount'],
                'loss_amount'           => null,
                'after_balance'         => $row['after_balance'],
            ],
            'date_info' => [
                'start_at'              => $row['add_date'],
                'end_at'                => $row['add_date'],
                'bet_at'                => $row['add_date'],
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null,
            ],
            'bet_details' => '',
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->utils->debug_log('MGW ', $data);
        return $data;

    }

    /**
    *
    * perpare original rows, include process unknown game, pack bet details, convert game status
    *
    * @param  array &$row
    */
    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        if(isset($row['bet_type'])){
            $row['bet_type'] = $row['bet_type'] == self::BET_TYPE_REAL_CODE ? self::BET_TYPE_REAL : '';
        }
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
        $game_type_id = null;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $game_description_id=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game_id'], $row['game_id']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }

    public function queryTransactionByDateTime($startDate, $endDate){

$transTable = $this->getTransactionsTable();


$sql = <<<EOD
SELECT
t.player_id as player_id,
t.updated_at as transaction_date,
t.balance_adjustment_amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.game_round_id as round_no,
t.external_uniqueid as external_uniqueid,
t.action as trans_type,
t.balance_adjustment_method balance_adjustment_method,
t.balance_adjustment_amount balance_adjustment_amount       
FROM {$transTable} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;


        // $startDate = strval(strtotime($startDate) * 1000);
        // $endDate = strval(strtotime($endDate) * 1000);
        $params=[$this->getPlatformCode(),$startDate, $endDate];


        $this->CI->utils->debug_log('MGW SEAMLESS GAME (queryTransactionByDateTime)', 'sql', $sql, 'params',$params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function processTransactions(&$transactions){
        $temp_game_records = [];

        if(!empty($transactions)){
            foreach($transactions as $transaction){

                $temp_game_record                       = [];
                $temp_game_record['player_id']          = $transaction['player_id'];
                $temp_game_record['game_platform_id']   = $this->getPlatformCode();
                $temp_game_record['transaction_date']   = $transaction['transaction_date'];
                $temp_game_record['amount']             = abs($transaction['amount']);
                $temp_game_record['before_balance']     = $transaction['before_balance'];
                $temp_game_record['after_balance']      = $transaction['after_balance'];
                $temp_game_record['round_no']           = $transaction['round_no'];
                $extra_info                             = @json_decode($transaction['extra_info'], true);
                $extra                                  = [];
                $extra['trans_type']                    = $transaction['trans_type'];
                $temp_game_record['extra_info']         = json_encode($extra);
                $temp_game_record['external_uniqueid']  = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type']  = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if($transaction['after_balance']<$transaction['before_balance']){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }



    public function queryPlayerBalanceByPlayerId($playerId) {

        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        return $result;
    }

    public function getTransactionsTable(){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transactions_table;
        }

        $date=new DateTime();
        $monthStr=$date->format('Ym');
        
        return $this->initGameTransactionsMonthlyTableByDate($monthStr);        
    }

	public function initGameTransactionsMonthlyTableByDate($yearMonthStr){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transactions_table;
        }

		$tableName=$this->original_transactions_table.'_'.$yearMonthStr;
		if (!$this->CI->utils->table_really_exists($tableName)) {
			try{
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like mgw_seamless_wallet_transactions');

			}catch(Exception $e){
				$this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
			}
		}

		return $tableName;
	}

    public function validateWhiteIP(){
        $success=false;
        $this->CI->load->model(['ip']);
        if(empty($this->backend_api_white_ip_list)){
            return true;
        }
        $ip=$this->utils->getIP();
        $this->utils->debug_log('MGW ip',$ip);
        if(is_array($this->backend_api_white_ip_list)){
            foreach ($this->backend_api_white_ip_list as $whiteIp) {
                if($this->utils->compareIP($ip, $whiteIp)){
                    $this->utils->debug_log('MGW found white ip', $whiteIp, $ip);
                    //found
                    return true;
                }
            }
        }
        $this->utils->debug_log('MGW validateWhiteIP status', $success);
        return $success;
    }

    // public function debitCreditAmountToWallet($params, &$previousBalance, &$afterBalance){
	// 	$this->utils->debug_log("MGW SEAMLESS: (debitCreditAmount)", $params, $previousBalance, $afterBalance);
	// 	//initialize params
	// 	$bet_amount 	    = isset($params['gold']) ? $this->gameAmountToDBTruncateNumber($params['gold']) : 0;
	// 	$player_id			= $params['player_id'];
	// 	$win_gold   		= isset($params['win_gold'])  ? $params['win_gold'] : 0;
	// 	$transfer_amount 	= $this->gameAmountToDBTruncateNumber($win_gold - $bet_amount);
	// 	$amount 			= abs($this->gameAmountToDBTruncateNumber($transfer_amount));


	// 	//initialize response
	// 	$success 				= false;
	// 	$isValidAmount 			= true;
	// 	$insufficientBalance 	= false;
	// 	$isAlreadyExists 		= false;
	// 	$isTransactionAdded 	= false;
	// 	$flagrefunded 			= false;
	// 	$additionalResponse		= [];

	// 	if($transfer_amount>=0){
	// 		$mode = 'credit';
	// 	}else{
	// 		$mode = 'debit';
	// 	}
	// 	$params['balance_adjustment_method'] = $mode;
	// 	$params['balance_adjustment_amount'] = $transfer_amount;

	// 	//get and process balance

    //     $queryPlayerBalance = $this->queryPlayerBalance($params['uid'])['balance'];

	// 	$get_balance = $queryPlayerBalance ? $queryPlayerBalance : 0;

	// 	//$existingBet = $this->CI->mgw_seamless_transactions->isTransactionExist($params['transaction_id'], x$mode);
	// 	$existingTrans = false;

	// 	$prevRoundData = [];
	// 	$check_bet_params = ['wager_id'=>strval($params['wager_id'])];
	// 	if(!isset($params['wager_id'])||empty($params['wager_id'])){
	// 		$check_bet_params = ['wager_id'=>strval($params['wager_id'])];
	// 	}
	// 	$currentTableName = $this->getTransactionsTable();
	// 	$this->utils->debug_log("MGW SEAMLESS SERVICE: (debitCreditAmount)", 'currentTableName', $currentTableName);
	// 	$currentRoundData = $this->CI->mgw_seamless_transactions->getRoundData($currentTableName, $check_bet_params);

    //     $checkOtherTable = $this->checkOtherTransactionTable();

	// 	if($this->force_check_other_transaction_table&&$this->use_monthly_transactions_table){
	// 		$checkOtherTable = true;
	// 	}

	// 	if($checkOtherTable){                    
	// 		# get prev table
	// 		$prevTranstable = $this->getTransactionsPreviousTable();

	// 		$this->utils->debug_log("MGW SEAMLESS SERVICE: (debitCreditAmount)", 'prevTranstable', $prevTranstable);
	// 		# get data from prev table
	// 		$prevRoundData = $this->CI->mgw_seamless_transactions->getRoundData($prevTranstable, $check_bet_params);
	// 	}

	// 	$roundData = array_merge($currentRoundData, $prevRoundData);   
	// 	foreach($roundData as $roundDataRow){
	// 		if($roundDataRow['wager_id']==$params['wager_id']){
	// 			$existingTrans = $roundDataRow;
	// 		}
	// 	}


	// 	# existing transactions/probably retry
	// 	if(!empty($existingTrans)){
	// 		$this->utils->error_log("MGW SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: existing ".$mode, $existingTrans);

	// 		//$getBetDetails = $this->CI->mgw_seamless_transactions->getExistingTransaction($params['transaction_id'], $mode);

	// 		$isAlreadyExists = true;
	// 		//Return the previous successful response for the duplicate request.
	// 		$previousBalance = $existingTrans['before_balance'];
	// 		$afterBalance = $existingTrans['after_balance'];
	// 		// $afterBalance = $previousBalance = $get_balance;
	// 		return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
	// 	}

	// 	# bet not existing proceed
	// 	if ($bet_amount > $get_balance){
	// 		$insufficientBalance = true;
	// 		$this->utils->debug_log("MGW SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: insufficientBalance bet_amount > get_balance", $insufficientBalance);
	// 		return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
	// 	}

	// 	if($amount<>0){

	// 		if($get_balance !== false){

	// 			$afterBalance = $previousBalance = $get_balance;
	// 			if($mode=='debit'){
	// 				$afterBalance = $afterBalance - $amount;
	// 			}else{
	// 				$afterBalance = $afterBalance + $amount;
	// 			}

	// 		}else{
	// 			$this->utils->error_log("MGW SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: getBalance", $get_balance, $this->request);
	// 			return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
	// 		}

	// 		if($mode=='debit' && $previousBalance < $amount ){
	// 			$afterBalance = $previousBalance;
	// 			$insufficientBalance = true;
	// 			$this->utils->debug_log("MGW SEAMLESS SERVICE: (debitCreditAmountToWallet) insufficientBalance", $insufficientBalance);
	// 			return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
	// 		}

	// 		//insert transaction
	// 		$isAdded = $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance, $flagrefunded);

	// 		if($isAdded===false){
	// 			$this->utils->error_log("MGW SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request);
	// 			return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
	// 		}

	// 		//rollback amount because it already been processed
	// 		if($isAdded==0){
	// 			$this->utils->debug_log("MGW SEAMLESS SERVICE: (debitCreditAmountToWallet) isAdded already", $isAdded, $this->trans_records);
	// 			$isAlreadyExists = true;
	// 			$afterBalance = $previousBalance;
	// 			return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
	// 		}else{
	// 			$isTransactionAdded = true;
	// 		}

	// 		$TransGuid = isset($params['transaction_id']) ? $params['transaction_id'] : null;
	// 		$uniqueid_of_seamless_service = $this->getPlatformCode().'-'.$TransGuid;
	// 		$external_game_id = isset($params['game_id']) ? $params['game_id'] : null;
	// 		$this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service, $external_game_id);

	// 		$success = $this->transferGameWallet($player_id, $this->getPlatformCode(), $mode, $amount, $afterBalance);

	// 		if(!$success){
	// 			$this->utils->error_log("MGW SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: debit/credit", $this->request);
	// 		}

	// 		if(is_null($afterBalance)){
	// 			$getBalance 	= $this->queryPlayerBalance($params['uid']);
	// 			$afterBalance 	= $getBalance['balance'] ? $getBalance['balance'] : 0;
	// 		}

	// 	}else{
	// 		if($get_balance!==false){
	// 			$afterBalance = $previousBalance = $get_balance;
	// 			$success = true;

	// 			//insert transaction
	// 			$this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance);
	// 		}else{
	// 			$success = false;
	// 		}
	// 	}
		

	// 	return array($success,
	// 					$previousBalance,
	// 					$afterBalance,
	// 					$insufficientBalance,
	// 					$isAlreadyExists,
	// 					$additionalResponse,
	// 					$isTransactionAdded);
	// }

    // public function insertIgnoreTransactionRecord($data, $previous_balance, $after_balance){
	// 	$data['after_balance'] = $after_balance;
	// 	$data['before_balance'] = $previous_balance;
	// 	$this->trans_record = $trans_record = $this->makeTransactionRecord($data);
	// 	$tableName = $this->getTransactionsTable();
    //     $this->CI->mgw_seamless_transactions->setTableName($tableName);  
	// 	$this->utils->debug_log("MGW tablename" , $tableName);
	// 	return $this->CI->mgw_seamless_transactions->insertIgnoreRow($trans_record);
	// }


    // public function transferGameWallet($player_id, $game_platform_id, $mode, $amount, &$afterBalance=null){
	// 	$success = false; 
	// 	//not using transferSeamlessSingleWallet this function is for seamless wallet only applicable in GW
	// 	if($mode=='debit'){
	// 		$success = $this->wallet_model->decSubWallet($player_id, $game_platform_id, $amount, $afterBalance);
	// 	}elseif($mode=='credit'){
	// 		$success = $this->wallet_model->incSubWallet($player_id, $game_platform_id, $amount, $afterBalance);
	// 	}

	// 	return $success;
	// }

    // public function makeTransactionRecord($raw_data){
	// 	$data = [];
	// 	$data['player_id']     	= isset($raw_data['player_id']) ? $raw_data['player_id'] : null;
	// 	$data['merchant_id']   	= isset($raw_data['merchant_id']) ? $raw_data['merchant_id'] : null;
	// 	$data['site_id']       	= isset($raw_data['site_id']) ? $raw_data['site_id'] : null;
	// 	$data['firm_id']       	= isset($raw_data['firm_id']) ? $raw_data['firm_id'] : null;
	// 	$data['game_id']		= isset($raw_data['game_id']) ? $raw_data['game_id'] : null;
	// 	$data['game_class'] 	= isset($raw_data['game_class']) ? $raw_data['game_class'] : null;
	// 	$data['risk'] 			= isset($raw_data['risk']) ? $raw_data['risk'] : null;
	// 	$data['game_round_id'] 	= isset($raw_data['game_round_id']) ? $raw_data['game_round_id'] : null;
	// 	$data['wager_id'] 		= isset($raw_data['wager_id']) ? $raw_data['wager_id'] : null;
	// 	$data['gold'] 			= isset($raw_data['gold']) ? $raw_data['gold'] : null;
	// 	$data['vgold'] 			= isset($raw_data['vgold']) ? $raw_data['vgold'] : null;
	// 	$data['win_gold'] 		= isset($raw_data['win_gold']) ? $raw_data['win_gold'] : null;
	// 	$data['unused_gold'] 	= isset($raw_data['unused_gold']) ? $raw_data['unused_gold'] : null;
	// 	$data['commission_gold']= isset($raw_data['commission_gold']) ? $raw_data['commission_gold'] : null;
	// 	$data['result'] 		= isset($raw_data['result']) ? $raw_data['result'] : null;
	// 	$data['order_date'] 	= isset($raw_data['order_date']) ? $raw_data['order_date'] : null;
	// 	$data['add_date'] 		= isset($raw_data['add_date']) ? $raw_data['add_date'] : null;
	// 	$data['result_date']  	= isset($raw_data['result_date']) ? $raw_data['result_date'] : null;
	// 	$data['freegame']     	= isset($raw_data['freegame']) ? $raw_data['freegame'] : null;
	// 	$data['utoken']       	= isset($raw_data['utoken']) ? $raw_data['utoken'] : null;
	// 	$data['uid']           	= isset($raw_data['uid']) ? $raw_data['uid'] : null;
	// 	$data['ip']            	= isset($raw_data['ip']) ? $raw_data['ip'] : null;
	// 	$data['device']		 	= isset($raw_data['device']) ? $raw_data['device'] : null;
	// 	$data['action']		 	= isset($raw_data['action']) ? $raw_data['action'] : null;
	// 	$data['currency']	 	= $this->getCurrency();

	// 	//common
    //     $data['trans_type'] 		                = isset($raw_data['trans_type']) ? $raw_data['trans_type'] : null;
    //     $data['trans_status'] 		                = isset($raw_data['trans_status']) ? $raw_data['trans_status'] : GAME_LOGS::STATUS_PENDING;
	// 	$data['elapsed_time'] 		                = intval($this->utils->getExecutionTimeToNow()*1000);
    //     $data['external_uniqueid']                  = isset($raw_data['external_uniqueid']) ? $raw_data['external_uniqueid'] : null;
	// 	$data['balance_adjustment_amount']          = isset($raw_data['balance_adjustment_amount']) ? $raw_data['balance_adjustment_amount'] : null;
	// 	$data['balance_adjustment_method']          = isset($raw_data['balance_adjustment_method']) ? $raw_data['balance_adjustment_method'] : null;
	// 	$data['before_balance'] 	                = isset($raw_data['before_balance']) ? floatVal($raw_data['before_balance']) : 0;
	// 	$data['after_balance'] 		                = isset($raw_data['after_balance']) ? floatVal($raw_data['after_balance']) : 0;
	// 	$data['game_platform_id'] 	                = $this->getPlatformCode();

	// 	return $data;
	// }

}

/*end of file*/