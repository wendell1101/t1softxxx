<?php
/**
 * EvenBet Poker Seamless game integration
 * OGP-27753
 *
 * @author  Kristallynn Tolentino
 *
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_evenbet_poker_seamless extends Abstract_game_api {

    const POST = 'POST';

    const SUCCESS = 200;

    const URI_MAP = [
        self::API_queryForwardGame => "/v2/app/",
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'round_id',
        'status',
        'updated_at'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'win_amount',
        'result_amount',
    ];

    const TRANSACTION_TYPE_DEBIT = 'debit';
    const TRANSACTION_TYPE_CREDIT = 'credit';

    public $client_id;
    public $default_secret_key;
    public $seamless_wallet_secret;
    public $original_transaction_table_name;
    public $conversion_rate;

    public function __construct() {
        parent::__construct();

        $this->api_url = $this->getSystemInfo('url', 'https://web-stage.jack-poker.com/api/web');
        $this->lang = $this->getSystemInfo('lang');
        $this->currency = $this->getSystemInfo('currency', 'BRL');
        $this->client_id = $this->getSystemInfo('client_id', 'ib-NlRvMlOv0J');
        $this->default_secret_key = $this->getSystemInfo('default_secret_key', 'Pt7aCS3WALYcoNJLwvcPDPYhku04Yk');
        $this->seamless_wallet_secret = $this->getSystemInfo('seamless_wallet_secret', 'ze1ukwuks5joav1guyfl28ocjj3zou');
        $this->conversion_rate = $this->getSystemInfo('conversion_rate', 100);

        $this->original_transaction_table_name = 'evenbet_poker_seamless_wallet_transactions';
    }

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode() {
        return EVENBET_POKER_SEAMLESS_GAME_API;
    }

    public function generateUrl($apiName, $params) {

        $uri = self::URI_MAP[$apiName]."users/".$params['userId']."/session?clientId=".$this->client_id;

        $url = $this->api_url . $uri;

        return $url;
    }

    protected function customHttpCall($ch, $params) {
        $headers = [
            'content-Type: application/x-www-form-urlencoded',
            'accept: application/vnd.api+json',
            'sign: ' . $this->generate_query_signature($params, $this->default_secret_key)
        ];

		$jsonDataEncoded = http_build_query($params);

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$this->utils->debug_log('Evenbet (customHttpCall) ', 'jsonDataEncoded', $jsonDataEncoded);
	}

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create account for EvenBet Poker Seamless";
        if($return){
            $success = true;
            $message = "Successfull create account for EvenBet Poker Seamless";
        }

        return array("success" => $success, "message" => $message);
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

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
        $this->CI->load->model(array('player_model'));

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $token = $this->getPlayerToken($playerId);

        $playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);

    	$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'playerName' => $playerName,
            'gameUsername' => $gameUsername
		);

        $params = array(
            "lang"=> $this->getLauncherLanguage($this->lang),
            "userId"=> $gameUsername,
            "authType"=> "external",
            "clientId"=> $this->client_id,
            "currency"=> $this->currency,
            "sessionId"=> $token
        );

		return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);


        $this->CI->utils->debug_log('Evenbet Poker resultArr: (' . __FUNCTION__ . ')', $resultArr);

        $result = array(
            "response_result_id" => $responseResultId,
            "success" => $success,
            "data" => $resultArr['data']['attributes']['redirect-url'],
            "auth" => $resultArr['data']['attributes']['auth'],
            "session-id" => $resultArr['data']['attributes']['session-id'],
        );

        $this->CI->utils->debug_log('Evenbet Poker Success: (' . __FUNCTION__ . ')', $success);

        if($success){
            $result['url'] = $resultArr['data']['attributes']['redirect-url'];
        }

        return array($success, $result);
	}

    public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en-us':
            case 'en_us':
            case 'EN':
            case 'en':
                $lang = 'en'; #english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'zh-cn':
            case 'zh_cn':
            case 'ZH':
            case 'zh':
                $lang = 'zh'; #chinese
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $apiName = '') {
        $success = false;

        $this->CI->utils->debug_log('Evenbet Poker: (' . __FUNCTION__ . ')', $resultArr);

        if(isset($resultArr['data'])){
            $success = true;
        }else{
            $this->setResponseResultToError($responseResultId);
        }

        return $success;
    }


    /* function sorting the query parameters
    * $array - array with the query parameters in the following format:
    * {parameter_name: value}
    */
    public function sortArray(&$array, $sortFlags = SORT_REGULAR){
      if (!is_array($array)) {
          return false;
      }

      // Sort array by parameter name
      return ksort($array, $sortFlags);
    }

    // Generate query signature
    # Step 1. Get the required data
    public function generate_query_signature($params, $secret_key){

        #Step 2. Delete the parameter 'clientId' from array with query parameters
        if (array_key_exists('clientId', $params)) {
            unset($params['clientId']);
        }

        #Step 3. Sort the parameters
        $this->sortArray($params);

         // Step 4. Concatenate the parameters into a string
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($params));
        $paramString = implode('', iterator_to_array($iterator));

         // Step 5. Add a secret key to the string
        $paramString = $paramString . $secret_key;

        $this->CI->utils->debug_log('EvenBet Poker paramString: (' . __FUNCTION__ . ')', $paramString);

        // Step 6. Generate a signature using the SHA256 algorithm
        $sign  = hash('sha256', $paramString);

        $this->CI->utils->debug_log('EvenBet Poker hash: (' . __FUNCTION__ . ')', $sign);

        return $sign;
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

        $sqlTime='transaction.updated_at >= ? and transaction.updated_at <= ?';

        if($use_bet_time) {
            $sqlTime='transaction.start_at >= ? and transaction.start_at <= ?';
        }
        $sql = <<<EOD
SELECT
    transaction.id as sync_index,
    transaction.amount as amount,
    transaction.bet_amount as bet_amount,
    transaction.win_amount as win_amount,
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
    {$this->original_transaction_table_name} as transaction
    LEFT JOIN game_description ON transaction.game_id = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON transaction.player_id = game_provider_auth.player_id and game_provider_auth.game_provider_id = ?
WHERE
transaction_type != "refunded"  and {$sqlTime} and transaction.game_platform_id = ?

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

        if(empty($row['md5_sum']))
        {
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}

        $bet_amount = isset($row['bet_amount']) ? $this->gameAmountToDBGameLogsTruncateNumber($row['bet_amount']) : 0;
        $data = [
            'game_info' => [
                'game_type_id'          => isset($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id'   => isset($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code'             => isset($row['game_code']) ? $row['game_code'] : null,
                'game_type'             => null,
                'game'                  => isset($row['game_description_name']) ? $row['game_description_name'] : null,
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => $bet_amount,
                'result_amount'         => $this->gameAmountToDBGameLogsTruncateNumber($row['result_amount']),
                'bet_for_cashback'      => $bet_amount,
                'real_betting_amount'   => $bet_amount,
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $this->gameAmountToDBGameLogsTruncateNumber($row['after_balance']),
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

        $this->utils->debug_log('evenbet ', $data);
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

        $row['result_amount'] = $row['win_amount'] - $row['bet_amount'];

        switch($row['status']) {
            case 'ok':
            case 'SETTLED':
                $row['status'] = Game_logs::STATUS_SETTLED;
                break;
            case 'REFUNDED':
                $row['note'] = 'Refund';
                $row['status'] = Game_logs::STATUS_REFUND;
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


    public function timestamps_milliseconds() {
        $date = new DateTimeImmutable();
        $timestampMs = (int) ($date->getTimestamp() . $date->format('v'));
        return $timestampMs;
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
FROM {$this->original_transaction_table_name} as t
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
                    $temp_game_record['amount'] = $this->gameAmountToDBGameLogsTruncateNumber($transaction['amount']);
                    $temp_game_record['before_balance'] = $this->gameAmountToDBGameLogsTruncateNumber($transaction['before_balance']);
                    $temp_game_record['after_balance'] = $this->gameAmountToDBGameLogsTruncateNumber($transaction['after_balance']);
                    $temp_game_record['round_no'] = $transaction['round_no'];
                    $extra_info = @json_decode($transaction['extra_info'], true);
                    $extra=[];
                    $extra['trans_type'] = $transaction['trans_type'];
                    $extra['extra'] = $extra_info;
                    $temp_game_record['extra_info'] = json_encode($extra);
                    $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                    if($transaction['trans_type'] == self::TRANSACTION_TYPE_DEBIT){
                        $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                    }else if($transaction['trans_type'] == self::TRANSACTION_TYPE_CREDIT){
                        $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                    }

                    $temp_game_records[] = $temp_game_record;
                    unset($temp_game_record);
                }
            }

            $transactions = $temp_game_records;

            $this->CI->utils->debug_log('evenbet: (' . __FUNCTION__ . ')', 'transactions:', $transactions);
        }
}

