<?php
/**
 * HotGraph game integration
 * OGP-25300
 *
 * @author  Kristallynn Tolentino
 *
 *
 * API DOC: https://uat.ambsuperapi.com
 *

 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_hotgraph_seamless extends Abstract_game_api {

    const POST = 'POST';
	const GET = 'GET';
	const PUT = 'PUT';

    const ORIGINAL_LOGS_TABLE_NAME = 'hotgraph_seamless_game_logs';
    const ORIGINAL_TRANSACTIONS = 'common_seamless_wallet_transactions';

    const FLAG_UPDATED = 1;
    const FLAG_NOT_UPDATED = 0;

    const URI_MAP = [
        self::API_createPlayer => "/seamless/logIn",
        self::API_login => "/seamless/logIn",
        self::API_syncGameRecords => "/seamless/betTransactionsV2",
        self::API_queryGameListFromGameProvider => '/seamless/games',
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'status',
        'updated_at'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'amount',
    ];

    const PAYOUT_STATUS_LOSE = "LOSE";
    const PAYOUT_STATUS_WIN = "WIN";
    const PAYOUT_STATUS_DRAW = "DRAW";
    const PAYOUT_STATUS_UNKNOWN = "UNKNOWN";

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';
    const TRANSACTION_CANCEL = 'cancel';

    public $product_id;

    public function __construct() {
        parent::__construct();

        $this->api_url = $this->getSystemInfo('url', 'https://test.ambsuperapi.com');
        $this->lang = $this->getSystemInfo('lang','th_TH');
        $this->currency = $this->getSystemInfo('currency', 'THB');

        $this->product_id = $this->getSystemInfo('product_id', 'HOTGRAPH');
        $this->x_api_key = $this->getSystemInfo('x_api_key', '8add672f-3bdb-4977-a0a6-59032f9808d8');
        $this->agent_username = $this->getSystemInfo('agent_username', 'SexyCasinoDev');
        $this->default_gamecode = $this->getSystemInfo('default_gamecode', 'Binary');
        $this->default_game_launch = $this->getSystemInfo('default_game_launch', 'https://hotgraph.com/demo');

        $this->original_transactions_table = self::ORIGINAL_TRANSACTIONS;
    }

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode() {
        return HOTGRAPH_SEAMLESS_API;
    }

    public function getCurrency() {
        return $this->currency;
    }

    public function generateUrl($apiName, $params) {
        $uri = self::URI_MAP[$apiName];
        $url = $this->api_url . $uri;
        return $url;
    }

    protected function customHttpCall($ch, $params) {
        $headers = [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->agent_username.":".$this->x_api_key)
        ];

        if($params["actions"]["method"] == self::POST)
        {

            $function = $params["actions"]['function'];

            unset($params["actions"]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, TRUE);

            if(isset($params["json_body"])){
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params["json_body"]));
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }

        }

	}

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create account for HOTGRAPH";
        if($return){
            $success = true;
            $message = "Successfull create account for HOTGRAPH.";
        }

        return array("success" => $success, "message" => $message);
    }

    public function login($playerName, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $player_token = $this->getPlayerTokenByUsername($playerName);

        if ($this->utils->is_mobile()) {
            $is_mobile = true;
        }else{
            $is_mobile = false;
        }

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

        $json_body = array(
            'username' => $playerName,
            'productId' => $this->product_id,
            'gameCode' => $this->default_gamecode,
            'isMobileLogin' => $is_mobile,
            'sessionToken' => $player_token,
            'betLimit' => []
        );

        $params = array(
            "json_body" => $json_body,
            "actions" => [
                "function" => self::API_login,
                "method" => self::POST
            ]
        );

        return $this->callApi(self::API_login, $params, $context);
	}

    public function processResultForLogin($params){
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

        $this->CI->utils->debug_log('HOTGRAPH: (' . __FUNCTION__ . ')', $resultArr['data']);

        if(isset($resultArr['data']) && !empty($resultArr['data'])){
            $result = array(
                "response_result_id" => $responseResultId,
                "success" => $success,
                "request_id" => $resultArr['reqId'],
                "code" => $resultArr['code'],
                "data" => $resultArr['data']['url'],
                "message" => $resultArr['message']
            );
        }else{
            $result = array(
                "response_result_id" => $responseResultId,
                "success" => $success,
                "request_id" => $resultArr['reqId'],
                "code" => $resultArr['code'],
                "data" => $this->default_game_launch,
                "message" => $resultArr['message'],
            );
        }

		return array($success, $result);
	}

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        $this->CI->utils->debug_log('HOTGRAPH: (' . __FUNCTION__ . ')', 'PARAMS:', $playerName, 'RESULT:', $result);

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

    public function queryForwardGame($playerName,$extra=[]) {
        $result = $this->login($playerName, $extra);

        $this->CI->utils->debug_log('HOTGRAPH: (' . __FUNCTION__ . ')', $result);

        if(isset($result['data'])){
            $url = $result['data'];
            $success = true;
        }else{
            $url = "";
            $success = false;
        }

        return array(
            "success" => $success,
            "url" => $url
        );
    }

    public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en-us':
                $lang = 'en_US'; // english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'zh-cn':
                $lang = 'zh_CN'; // chinese
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id-id':
                $lang = 'id_ID';
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi-vn':
                $lang = 'vi_VN';
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko-kr':
                $lang = 'ko_KR';
                break;
            case Language_function::INT_LANG_THAI:
            case 'th-th':
                $lang = 'th_TH';
                break;
            default:
                $lang = 'en_US'; // default as english
                break;
        }
        return $lang;
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $apiName = '') {
        $success = false;

        $this->CI->utils->debug_log('HOTGRAPH: (' . __FUNCTION__ . ')', $resultArr);

        if(isset($resultArr['message']) && $resultArr['message']=="Success"){
            $success = true;
            $this->utils->debug_log('HOTGRAPH: (' . __FUNCTION__ . ') - NO ERROR', $apiName, $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        } else {
            $this->setResponseResultToError($responseResultId);
            $this->utils->debug_log('HOTGRAPH: (' . __FUNCTION__ . ') - HAS ERROR', $apiName, $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }
        return $success;
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    ////////////////////////////////////////////

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
        $table = 'common_seamless_wallet_transactions';
        $sqlTime='transaction.updated_at >= ? and transaction.updated_at <= ?';

        if($use_bet_time) {
            $sqlTime='transaction.start_at >= ? and transaction.start_at <= ?';
        }
        $sql = <<<EOD
SELECT
    transaction.id as sync_index,
    transaction.bet_amount as bet_amount,
    transaction.amount as result_amount,
    transaction.before_balance,
    transaction.after_balance,
    transaction.status,
    transaction.start_at,
    transaction.end_at,
    transaction.transaction_type,
    transaction.game_id,

    transaction.external_unique_id as external_uniqueid,
    transaction.updated_at,
    transaction.response_result_id,
    transaction.round_id,
    transaction.md5_sum,

    game_provider_auth.login_name as player_username,
    game_provider_auth.player_id,

    game_description.id as game_description_id,
    game_description.game_name as game_description_name,
    game_description.game_code as game_code,
    game_description.game_type_id
FROM
    {$table} as transaction
    LEFT JOIN game_description ON transaction.game_id = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON transaction.player_id = game_provider_auth.player_id and game_provider_auth.game_provider_id = ?
WHERE
transaction.transaction_type != 'cancel' and {$sqlTime} and transaction.game_platform_id = ?

EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode(),
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;


    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        if(!array_key_exists('md5_sum', $row)){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        $bet_amount = ($row['transaction_type'] == self::TRANSACTION_DEBIT) ? abs($row['bet_amount']) : abs($row['bet_amount']);
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
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => $bet_amount,
                'result_amount'         => $row['result_amount'],
                'bet_for_cashback'      => $bet_amount,
                'real_betting_amount'   => $bet_amount,
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $row['after_balance'],
            ],
            'date_info' => [
                'start_at'              => $row['start_at'],
                'end_at'                => $row['end_at'],
                'bet_at'                => $row['start_at'],
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
                'bet_type'              => null
            ],
            'bet_details' => "",
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->utils->debug_log('HOTGRAPH ', $data);
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
        switch($row['status']) {
            case 'ok':
            case 'SETTLED':
                $row['status'] = Game_logs::STATUS_SETTLED;
                break;
            default:
                $row['status'] = Game_logs::STATUS_PENDING;
                break;
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

$sql = <<<EOD
SELECT
t.player_id as player_id,
t.created_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_unique_id as external_uniqueid,
t.transaction_type trans_type,
t.extra_info extra_info
FROM {$this->original_transactions_table} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;

$params=[$this->getPlatformCode(),$startDate, $endDate];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function processTransactions(&$transactions){
        $temp_game_records = [];

        if(!empty($transactions)){
            foreach($transactions as $transaction){

                $temp_game_record = [];
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];
                $temp_game_record['amount'] = abs($transaction['amount']);
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];
                $extra_info = @json_decode($transaction['extra_info'], true);
                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $extra['extra'] = $extra_info;
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if(in_array($transaction['trans_type'], ['debit'])){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function triggerInternalPayoutRound($transaction) {
        $this->CI->load->model('common_token');
        $this->CI->utils->debug_log('HOTGRAPH SEAMLESS (triggerInternalPayoutRound)', 'transaction', $transaction);

        $transaction = json_decode($transaction);
        $transaction->token = $this->CI->common_token->getPlayerCommonTokenByGameUsername($transaction->uid);
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForTriggerInternalPayoutRound',
            'gameUsername' => $transaction->uid,
            'transaction' => $transaction
        ];

        return $this->callApi(self::API_triggerInternalPayoutRound, $transaction, $context);
    }

    public function processResultForTriggerInternalPayoutRound($params){
        $resultArr = $this->getResultJsonFromParams($params);

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $transaction = $this->getVariableFromContext($params, 'transaction');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $apiName = $this->getVariableFromContext($params, 'apiName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername, $apiName);
        $result = array(
            'gameUsername' => $gameUsername,
            'transaction' => $transaction,
            'status' => null,
            'exists' => null,
            'triggered_payout' => false
        );
        if($success){
            $result['status'] = true;
            $result['triggered_payout'] = true;
            $success = true;
        }
        $result['message'] = isset($resultArr['errorDescription']) ? $resultArr['errorDescription'] : '';

        return [$success, $result];
    }

}
