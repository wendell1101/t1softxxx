<?php
/**
 * BETBY Single Wallet API Document
 * OGP-29178
 *
 * @author  Jerbey Capoquian
 *
 *
 * 
 *
 * By function:
    
 *
 * 
 * Related File
     - betby_seamless_service_api.php
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/../../core-lib/application/libraries/third_party/jwt_v6/jwt.php';
require_once dirname(__FILE__) . '/../../core-lib/application/libraries/third_party/jwt_v6/key.php';

class Game_api_betby_seamless extends Abstract_game_api {

    const ORIGINAL_TRANSACTION_TABLE = 'betby_seamless_wallet_transactions';
    const ORIGINAL_LOGS_TABLE_NAME = 'betby_seamless_game_logs';

    const OPERATION_BET = 'bet';
    const OPERARTION_LOST = 'lost';
    
    public function __construct() {
        parent::__construct();
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;

        $this->api_url = $this->getSystemInfo('url');
        $this->lobby_url = $this->getSystemInfo('lobby_url');
        $this->currency = $this->getSystemInfo('currency', "BRL");
        $this->redirect = $this->getSystemInfo('redirect', false);
        $this->force_lang = $this->getSystemInfo('force_lang', false);
        $this->language_code = $this->getSystemInfo('language_code', 'en');
        $this->brand_id = $this->getSystemInfo('brand_id', '2257881432301113344');
        $this->theme = $this->getSystemInfo('theme', 'default');
        $this->js_link = $this->getSystemInfo('js_link', 'https://ui.invisiblesport.com/bt-renderer.min.js');#stg
        $this->cashier_url = $this->getSystemInfo('cashier_url');

        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);

        $this->allow_launch_demo_without_authentication=$this->getSystemInfo('allow_launch_demo_without_authentication', true);
    }

    const URI_MAP = array(
    );

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode() {
        return BETBY_SEAMLESS_GAME_API;
    }

    /**
     * overview : custom http call
     *
     * @param $ch
     * @param $params
     */
    protected function customHttpCall($ch, $params) {
        $signature = md5(json_encode($params).$this->api_key);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));  
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: {$signature}"));   
    }

    protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        return $errCode || intval($statusCode, 10) >= 503;
    }

    public function generateUrl($apiName, $params) {
        $apiUri = self::URI_MAP[$apiName];
        $url = $this->api_url . $apiUri;
        return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode) { 
        $success = false;
        if(@$statusCode == 200 && isset($resultArr['code']) && $resultArr['code'] == self::ERROR_CODE_SUCCESS){
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('BB got error ', $responseResultId,'result', $resultArr);
        }
        return $success;     
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for BB Game";
        if($return){
            $success = true;
            $message = "Successfull create account for BB Game.";
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        return array("success" => $success, "message" => $message);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null){
        $external_transaction_id = $transfer_secure_id;

        return [
            "success" => true,
            "external_transaction_id" => $external_transaction_id,
            "response_result_id" => null,
            "didnot_insert_game_logs" => true
        ];
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $external_transaction_id = $transfer_secure_id;

        return [
            "success" => true,
            "external_transaction_id" => $external_transaction_id,
            "response_result_id" => null,
            "didnot_insert_game_logs" => true
        ];
    }

    public function queryForwardGame($playerName, $extra = null) {
        $this->CI->load->model(array('common_token', 'external_common_tokens'));
        $language = isset($extra['language']) ? $extra['language'] : LANGUAGE_FUNCTION::INT_LANG_ENGLISH;
        if(is_null($playerName)){
            $demo_result =  array(
                "js_link" => $this->js_link,
                "success" => true,
                "brand_id" => $this->brand_id,
                "jwt_token" => null,
                "theme" => $this->theme,
                "lang" => $this->getLanguage($language),
                "login_url" => $this->utils->getSystemUrl('player','/iframe/auth/login'),
                "register_url" => $this->utils->getSystemUrl('player','/player_center/iframe_register'),
                "cashier_url" => empty($this->cashier_url) ? $this->utils->getSystemUrl('player') : $this->cashier_url,
            );
            return $demo_result;
        }

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'playerId' => $playerId,
            'gameUsername' => $gameUsername
        );
        
        $expireTimesatmp = date('Y-m-d H:i:s', strtotime("+5 min"));
        $now = date("Y-m-d H:i:s");
        $token = $this->getPlayerToken($playerId); #generate new one incase no valid token
        // $external_token = $this->CI->external_common_tokens->getExternalToken($playerId, $this->getPlatformCode());
        // if(!empty($external_token)){
        //     if($token == $external_token){
        //         $token_info = $this->CI->external_common_tokens->getExternalTokenInfo($playerId, $token);
        //         $extra = isset($token_info['extra_info']) ? $token_info['extra_info'] : [];
        //         if(!empty($extra)){
        //             $external_result = json_decode($extra, true);
        //             $external_result['external_token'] = true;
        //             $jwtToken = $external_result['jwt_token'];
        //             $jwt = new JWT;
        //             $publicKey = $this->getPublicKey();
        //             try{
        //                 $payload = $jwt->decode($jwtToken, new KEY($publicKey, 'ES256')); 
        //             } catch (Exception $e) {
        //                 $payload = $e->getMessage();
        //             }

        //             if($payload != "Expired token"){
        //                 $external_result['success'] = true;
        //                 return  $external_result;
        //             }
        //         }
        //     }
        // }
        $jti = $token.$this->utils->getTimestampNow();

        $timeoutDateTime = $this->CI->common_token->getTokenTimeout($token);
        $timeoutTimestamp = strtotime($timeoutDateTime);
        $params = array(
            'iss' => $this->brand_id,
            'sub' => $gameUsername,
            'name' =>  $gameUsername,
            'iat' => strtotime("now"),
            'exp' => $timeoutTimestamp,
            // 'exp' =>  date('Y-m-d H:i:s', strtotime("+5 min")),
            // 'jti' => $token,
            'jti' => $jti,
            'lang' => $this->getLanguage($language),
            'currency' => $this->currency,
        );

        $privateKey = $this->getPrivateKey();
        $jwt = new JWTBetby;
        $jwtToken = $jwt->encode($params, $privateKey, "ES256");

        $results = array(
            "js_link" => $this->js_link,
            "success" => true,
            "brand_id" => $this->brand_id,
            "jwt_token" => $jwtToken,
            "lang" => $params['lang'],
            // "player_token" => $token,
            "theme" => !empty($this->theme) ? $this->theme : "default",
            "login_url" => $this->utils->getSystemUrl('player','/iframe/auth/login'),
            "register_url" =>  $this->utils->getSystemUrl('player','/player_center/iframe_register'),
            "cashier_url" => empty($this->cashier_url) ? $this->utils->getSystemUrl('player') : $this->cashier_url,
        );
        
        $this->CI->external_common_tokens->addPlayerTokenWithExtraInfo($playerId,
            $token,
            json_encode($results),
            $this->getPlatformCode(),
            $this->currency
        );

        return $results;

        /* testng encoding/decoding
        $privateKey = $this->getPrivateKey();
        $jwt = new JWT;
        $token = $jwt->encode(['message' => 'betby'], $privateKey, "ES256");
        var_dump($jwtToken);
        $publicKey = $this->getPublicKey();
        $payload = $jwt->decode($jwtToken, new KEY($publicKey, 'ES256'));
        print_r($payload);
        exit();
        */
    }

    # Returns the public key generated by us for testing
    # link of sample generating  pub and private key https://8gwifi.org/jwsgen.jsp JWS algo ES256
    private function getPublicKey() {
        $publicKey = $this->getSystemInfo('t1_pub_key'); #provide by provider
        // $publicKey = 'MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEuqzDe/cSxVo5gk1SYHRUabiaKLA5QTY0IYqvWkOY6PiprxCjJUxleEyyra8RZhG/LLxzIapLT4mfJkrjZbp+XQ==';
        /*
         -----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEuqzDe/cSxVo5gk1SYHRUabiaKLA5
QTY0IYqvWkOY6PiprxCjJUxleEyyra8RZhG/LLxzIapLT4mfJkrjZbp+XQ==
-----END PUBLIC KEY-----
*/

        $publicKey = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($publicKey, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return openssl_get_publickey($publicKey);
    }

    # Return the private key generated by us(Tripleonetech)
    # can use this site https://8gwifi.org/jwsgen.jsp and JWS algo ES256 to generate private key and public key 
    # note don't share private key, provide public key only on provider to use on decoding jwt token we generate
    private function getPrivateKey() {
        $privateKey = $this->getSystemInfo('t1_private_key');#provide by us (Tripleonetech)
        // $privateKey = 'MHcCAQEEIBApfmQ1muLOgnYSlTfBcdRWjVKNEgj1RudGNY8TWQ9voAoGCCqGSM49AwEHoUQDQgAEuqzDe/cSxVo5gk1SYHRUabiaKLA5QTY0IYqvWkOY6PiprxCjJUxleEyyra8RZhG/LLxzIapLT4mfJkrjZbp+XQ==';
        /*
         -----BEGIN EC PRIVATE KEY-----
MHcCAQEEIBApfmQ1muLOgnYSlTfBcdRWjVKNEgj1RudGNY8TWQ9voAoGCCqGSM49
AwEHoUQDQgAEuqzDe/cSxVo5gk1SYHRUabiaKLA5QTY0IYqvWkOY6PiprxCjJUxl
eEyyra8RZhG/LLxzIapLT4mfJkrjZbp+XQ==
-----END EC PRIVATE KEY-----
*/

        $privateKey = '-----BEGIN EC PRIVATE KEY-----' . PHP_EOL . chunk_split($privateKey, 64, PHP_EOL) . '-----END EC PRIVATE KEY-----' . PHP_EOL;
        return openssl_get_privatekey($privateKey);
    }

    # Returns the public key generated by provider
    private function getProviderPubKeyForDecodingPayload() {
        $publicKey = $this->getSystemInfo('betby_pub_key'); #provide by provider
        // $publicKey = 'MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAxasGC2ORvr11XjqU5bQ/aWqO+3mq/DYhF6lZDtcP4E0oluZ0Ta8YpOMa0tA8ajqDfz8CXNO9T80/ENroiBUDVUCjFsvAVIvdCk8jLuLvG+dUWC7lJ2xPsg7ncIAFU8U3guaSeFERvfCKJHh9W9fk7KguCedOCcgr1m+5ni4srtjFYxUqPp+DT2+3Q7alXKFrtVeCENycolJ7YKSR+l2lCSK2EUxPpwk+TIgCZz0tLdsNd54jqQTfdABYsnhxb8zTq7NbupekfZ73Y4pLmMHum/qqi2kHAoxJaU4ew5YdrWJZwoEhUH1Fzyq3BRqDIJ+X0DJfgi2RYvqnni8C9N12tu+7Nh0FHHF9Hc98R7enBwp6RDU0GY7clbmZJQwiz/npWCQ3meJl8gzc0b1eT8kAIG86Pojdp12K4C+rvO/IHKPBCaFUyxMwt4R/nSH7eJTRwzGwGKM/xpoM9mEHcvffzf5Rzf9+B5paRfJXKig9ZmbRbJb9x6+soPJ3wx9tzWjv2wvZW8ui8+5jyLjp5UF536E5jY/66LbUIF3hbKne8H8radBOJbFSxwoXE/jrI+DDo43eYJq7OqSO943Fih2zt11CUlAhNRgrz6dnEUYYc9/aD9nF1F6zC8YqzZTM45I1Hxm1Bmov+q3Cvzs6uDYbWuScKQboLtRoGqDGGv+2wz8CAwEAAQ==';
        /*
         -----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAxasGC2ORvr11XjqU5bQ/
aWqO+3mq/DYhF6lZDtcP4E0oluZ0Ta8YpOMa0tA8ajqDfz8CXNO9T80/ENroiBUD
VUCjFsvAVIvdCk8jLuLvG+dUWC7lJ2xPsg7ncIAFU8U3guaSeFERvfCKJHh9W9fk
7KguCedOCcgr1m+5ni4srtjFYxUqPp+DT2+3Q7alXKFrtVeCENycolJ7YKSR+l2l
CSK2EUxPpwk+TIgCZz0tLdsNd54jqQTfdABYsnhxb8zTq7NbupekfZ73Y4pLmMHu
m/qqi2kHAoxJaU4ew5YdrWJZwoEhUH1Fzyq3BRqDIJ+X0DJfgi2RYvqnni8C9N12
tu+7Nh0FHHF9Hc98R7enBwp6RDU0GY7clbmZJQwiz/npWCQ3meJl8gzc0b1eT8kA
IG86Pojdp12K4C+rvO/IHKPBCaFUyxMwt4R/nSH7eJTRwzGwGKM/xpoM9mEHcvff
zf5Rzf9+B5paRfJXKig9ZmbRbJb9x6+soPJ3wx9tzWjv2wvZW8ui8+5jyLjp5UF5
36E5jY/66LbUIF3hbKne8H8radBOJbFSxwoXE/jrI+DDo43eYJq7OqSO943Fih2z
t11CUlAhNRgrz6dnEUYYc9/aD9nF1F6zC8YqzZTM45I1Hxm1Bmov+q3Cvzs6uDYb
WuScKQboLtRoGqDGGv+2wz8CAwEAAQ==
-----END PUBLIC KEY-----
*/

        $publicKey = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($publicKey, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return openssl_get_publickey($publicKey);
    }

    public function deCodePayloadUsingRSA($payload){
        $publicKey = $this->getProviderPubKeyForDecodingPayload();
        $jwt = new JWTBetby;
        $request = $jwt->decode($payload, new KEY($publicKey, 'RS256'));
        $request = json_encode($request);
        $request = json_decode($request, true);
        return $request;
    }

    public function getLanguage($currentLang) {

        if($this->force_lang && $this->language_code){
            return $this->language_code;
        }

        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_CHINESE :
                $language = 'zh';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_INDONESIAN :
                $language = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_VIETNAMESE :
                $language = 'vi';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_KOREAN :
                $language = 'ko';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case LANGUAGE_FUNCTION::PLAYER_LANG_THAI :
                $language = 'th ';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_PORTUGUESE :
                $language = 'pt-br';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDIA:
            case LANGUAGE_FUNCTION::PLAYER_LANG_INDIA :
                $language = 'hi';
            case LANGUAGE_FUNCTION::INT_LANG_KAZAKH:
            case LANGUAGE_FUNCTION::PLAYER_LANG_KAZAKH :
                $language = 'kk';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_SPANISH:
            case LANGUAGE_FUNCTION::PLAYER_LANG_SPANISH :
                $language = 'es';
                break;
            default:
                $language = 'en';
                break;
        }
        return $language;
    }

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;
        $callbackQuery = 'queryOriginalGameLogs';

        if(!$this->enable_merging_rows){
            $callbackQuery = 'queryOriginalGameLogsUnmerge';
        }
        return $this->commonSyncMergeToGameLogs($token,
                $this,
                [$this, $callbackQuery],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
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
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time = false){
        $sqlTime="bb.updated_at >= ? AND bb.updated_at <= ? and bb.bet_transaction_id is not null";

        $sql = <<<EOD
SELECT
DISTINCT(bb.bet_transaction_id) 
FROM betby_seamless_wallet_transactions as bb
WHERE
{$sqlTime}
EOD;
        $params=[
            $dateFrom,
            $dateTo,
            $this->getPlatformCode()
        ];
        $this->CI->utils->debug_log('query distinct bet transacstion bb merge sql', $sql, $params);
        $bet_transaction_ids = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        $bet_transaction_ids = array_column($bet_transaction_ids, 'bet_transaction_id');

        $result = [];
        if(!empty($bet_transaction_ids)){
            $result = $this->preProcessTransactions($bet_transaction_ids);
        }

        return $result;
    }

    public function queryOriginalGameLogsUnmerge($dateFrom, $dateTo, $use_bet_time = false){
        $sqlTime="bb.updated_at >= ? AND bb.updated_at <= ? and bb.betslip_id is not null";

        $sql = <<<EOD
SELECT
DISTINCT(bb.betslip_id)
#bb.transaction_id,
#bb.bet_transaction_id
FROM betby_seamless_wallet_transactions as bb
WHERE
{$sqlTime}
EOD;
        $params=[
            $dateFrom,
            $dateTo,
            $this->getPlatformCode()
        ];
        $this->CI->utils->debug_log('query betslip_id bb unmerged sql', $sql, $params);
        $betslip_ids = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        $betslip_ids = array_column($betslip_ids, 'betslip_id');

        $result = [];
        if(!empty($betslip_ids)){
            $result = $this->preProcessTransactionsUnmerge($betslip_ids);
        }

        return $result;
    }

    private function preProcessTransactions( array $betslip_ids){
        $result = [];
        if(!empty($betslip_ids)){
            foreach ($betslip_ids as $key => $betslip_id) {
                $bet_details = $this->queryBet($betslip_id);
                $bet_slips = isset($bet_details['betslip']) ? json_decode($bet_details['betslip'], true) : [];
                $result_details = $this->queryResult($betslip_id);
                $last_row_details = $this->queryLast($result_details['last_id']);
                $selections = isset($last_row_details['selections']) ? json_decode($last_row_details['selections'], true) : [];

                $bet_info = array(
                    "bets" => isset($bet_slips['bets']) ? $bet_slips['bets'] : [],
                    "selections" => $selections
                );

                $bet_details['bet_details'] = $bet_info;
                // $this->rebuildBetDetailsFormat($bet_details, "test");

                if($result_details['result_amount'] >= 0){
                    $bet_details['result_amount'] += $result_details['result_amount'];
                }

                if(isset($result_details['end_at'])){
                    $bet_details['end_at'] = $result_details['end_at'];
                }

                if(isset($last_row_details['after_balance'])){
                    $bet_details['after_balance'] = $last_row_details['after_balance'];
                }

                if(!empty($last_row_details['status'])){
                    $bet_details['status'] = $last_row_details['status'];
                }

                $bet_details['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($bet_details, ['external_uniqueid', 'end_at', 'status'], ['bet_amount', 'result_amount']);

                $result[] = $bet_details;
            }

        }
    }

    private function preProcessTransactionsUnmerge( array $bet_transaction_ids){
        $result = [];
        if(!empty($bet_transaction_ids)){
            foreach ($bet_transaction_ids as $key => $bet_transaction_id) {
                $bet_details = $this->queryBetAndSettlement($bet_transaction_id); 
                foreach($bet_details as $item){
                    $bet_slips = isset($item['betslip']) ? json_decode($item['betslip'], true) : [];

                    $result_details = $this->queryResultUnmerge($bet_transaction_id);

                    $last_row_details = $this->queryLast($result_details['last_id']);
                    $selections = isset($last_row_details['selections']) ? json_decode($last_row_details['selections'], true) : [];
    
                    $bet_info = array(
                        "bets" => isset($bet_slips['bets']) ? $bet_slips['bets'] : [],
                        "selections" => $selections
                    );

                    $item['external_uniqueid'] = $item['operation'].'-'.$item['external_uniqueid'];

                    $item['status'] = $result_details['status'];
    
                    $item['bet_details'] = $bet_info;

                    $item['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($item, ['external_uniqueid', 'end_at', 'status'], ['bet_amount', 'result_amount']);
                    $result[] = $item;
                } 
            }

        }
        return $result;
    }

    public function queryLast($id){
        $this->CI->load->model('original_game_logs_model');
        $sqlId="bb.id = ?";
        $sql = <<<EOD
SELECT
bb.after_balance,
bb.sbe_status as status,
bb.selections
FROM betby_seamless_wallet_transactions as bb
WHERE
{$sqlId}
EOD;
        $params=[
            $id
        ];

        $this->CI->utils->debug_log('BB queryLast sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        return $result; 
    }

    private function queryBet($bet_transaction_id){
        $this->CI->load->model('original_game_logs_model');
        $sql = <<<EOD
SELECT
bb.id as sync_index,
bb.bet_amount,
bb.result_amount,
bb.date_time as start_at,
bb.date_time as end_at,
bb.betslip_id as external_uniqueid,
bb.betslip_id as round_number,
bb.after_balance, 
bb.sbe_status as status,
"sportsbook" as game_id,
"sportsbook" as game_name,
"sportsbook" as game_code,
bb.sbe_player_id as player_id,
bb.betslip,
bb.response_result_id,
gd.id as game_description_id,
gd.game_type_id

FROM betby_seamless_wallet_transactions as bb
LEFT JOIN game_description as gd ON "sportsbook" = gd.external_game_id AND gd.game_platform_id = ?
WHERE
bb.id = ?
EOD;

        $params=[
            $this->getPlatformCode(),
            $bet_transaction_id,
        ];

        $this->CI->utils->debug_log('BB queryBet sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        
        return $result;  
    }
    private function queryBetAndSettlement($betslip_id){
        $this->CI->load->model('original_game_logs_model');
        $sql = <<<EOD
SELECT
bb.id as sync_index,
bb.bet_transaction_id,
CASE 
    WHEN bb.operation != 'bet' THEN (SELECT bet_amount FROM betby_seamless_wallet_transactions WHERE betslip_id = {$betslip_id} and operation='bet' limit 1)
    ELSE bb.bet_amount
END as amount,
bb.bet_amount,
bb.result_amount,
bb.date_time as start_at,
bb.date_time as end_at,
bb.betslip_id as external_uniqueid,
bb.betslip_id as round_number,
bb.after_balance, 
"sportsbook" as game_id,
"sportsbook" as game_name,
"sportsbook" as game_code,
bb.sbe_player_id as player_id,
bb.betslip,
bb.operation,
bb.response_result_id,
gd.id as game_description_id,
gd.game_type_id

FROM betby_seamless_wallet_transactions as bb
LEFT JOIN game_description as gd ON "sportsbook" = gd.external_game_id AND gd.game_platform_id = ?
WHERE
bb.betslip_id = ?
EOD;

        $params=[
            $this->getPlatformCode(),
            $betslip_id,
        ];

        $this->CI->utils->debug_log('BB queryBetAndSettle sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;  
    }
    
    private function queryResult($bet_transaction_id) {
        $this->CI->load->model('original_game_logs_model');
        $where="bb.bet_transaction_id = ?";

        $sql = <<<EOD
SELECT
max(bb.id) as last_id,
sum(bb.bet_amount) as bet_amount,
sum(bb.result_amount) as result_amount,
max(bb.date_time) as end_at

FROM betby_seamless_wallet_transactions as bb
WHERE
{$where}
EOD;
        $params=[
            $bet_transaction_id,
            $this->getPlatformCode()
        ];

        $this->CI->utils->debug_log('BB queryResult sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        return $result;  
    }

    private function queryResultUnmerge($betslip_id) {
        $this->CI->load->model('original_game_logs_model');
        $where="bb.betslip_id = ?";

        $sql = <<<EOD
SELECT
bb.id as last_id,
bb.bet_amount as bet_amount,
bb.result_amount,
CASE 
    WHEN (SELECT COUNT(id) FROM betby_seamless_wallet_transactions WHERE betslip_id = {$betslip_id}) > 1 THEN 
        (CASE 
            WHEN bb.operation = 'bet' THEN 
                (SELECT MIN(sbe_status) FROM betby_seamless_wallet_transactions WHERE betslip_id = {$betslip_id} AND operation <> 'bet' LIMIT 1)
            ELSE 
                MIN(bb.sbe_status)
        END)
    ELSE 
        bb.sbe_status
END AS status,


bb.sbe_status,
after_balance,
bb.date_time as end_at
FROM betby_seamless_wallet_transactions as bb
WHERE
{$where}
EOD;
        $params=[
            $betslip_id,
            $this->getPlatformCode()
        ];

        $this->CI->utils->debug_log('BB queryResult for unmerge sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        return $result;  
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row) {
        if(empty($row['md5_sum'])){
            $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        $result_amount = isset($row['result_amount']) ? $row['result_amount'] : 0;
        $amount = isset($row['amount']) ? $row['amount'] : 0;
        $bet_amount = isset($row['bet_amount']) ? $row['bet_amount'] : 0;
        $after_balance = isset($row['after_balance']) ? $row['after_balance'] : null;

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_code'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => null,
            ],
            'amount_info' => [
                'bet_amount' => $bet_amount,
                'result_amount' => $result_amount,
                'bet_for_cashback' => isset($row['bet_amount']) ? $row['bet_amount'] : 0,
                'real_betting_amount' => isset($row['bet_amount']) ? $row['bet_amount'] : 0,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $after_balance,
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['start_at'],
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
                // 'bet_type' => $this->getBetTypeIdString($row['bet_type_id']),
                'bet_type' => null
            ],
            'bet_details' => $row['bet_details'],
            'extra' => [],
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
    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
    }

    public function getGameDescriptionInfo($row, $unknownGame) {

        $game_description_id = null;
        $game_name = $row['game_name'];
        $external_game_id = $row['game_code'];

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id);
    }

    public function queryTransactionByDateTime($startDate, $endDate){
        $this->CI->load->model(array('original_game_logs_model'));

        $original_transactions_table = $this->getTransactionsTable();

        $sql = <<<EOD
SELECT
bb.sbe_player_id as player_id,
bb.created_at transaction_date,
bb.amount as amount,
bb.after_balance as after_balance,
bb.before_balance as before_balance,
bb.betslip_id as round_no,
bb.external_unique_id as external_uniqueid,
bb.action trans_type
FROM betby_seamless_wallet_transactions as bb
WHERE `bb`.`created_at` >= ? AND `bb`.`created_at` <= ?  AND `bb`.sbe_player_id is not null
ORDER BY bb.created_at asc;
EOD;
        
        $params=[$startDate, $endDate];
        
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function rebuildBetDetailsFormat($row, $game_type) {
        $bet_details = is_array($row['bet_details']) ? $row['bet_details'] : json_decode($row['bet_details'], true);
        return $bet_details;
    }

    public function getTransactionsTable(){
        return $this->original_transaction_table;
    }
}
