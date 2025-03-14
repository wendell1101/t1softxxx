<?php
/**
 * Beter Sports Integration [Betsy Game]
 * OGP-29599
 *
 * API Docs
 * https://docs.betsy.gg/#/about/integration_process
 * 
 * Related File
 * - beter_sports_service_api.php
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_beter_sports_seamless extends Abstract_game_api {
    const POST = 'POST';
    const SUCCESS = 200;

    const ERROR_CODE_SUCCESS = [
        'ErrorCode' => 0,
        'ErrorMsg' => 'Success'
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'round_id',
        'status',
        'updated_at'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'result_amount',
    ];

    const ORIGINAL_TRANSACTION_TABLE = 'beter_sports_seamless_wallet_transactions';
    const TRANSACTION_TYPE_BET = 'bet';
    const TRANSACTION_TYPE_END= 'end';
    const TRANSACTION_TYPE_CANCEL = 'cancel';
    const TRANSACTION_TYPE_REFUND = 'refund';

    public $CI;
    public $api_url;
    public $lang;
    public $currency;
    public $cid;
    public $partner_secret;
    public $use_default_home_url;
    public $country;
    public $default_home_url;
    public $current_domain;
    public $conversion_rate;
    public $original_transaction_table;
    public $returnUrl;

    public function __construct() {
        parent::__construct();

        $this->api_url = $this->getSystemInfo('url', 'https://iframe-stage.beter.co');
        $this->lang = $this->getSystemInfo('lang', 'en');
        $this->currency = $this->getSystemInfo('currency', 'IDR');
        $this->country = $this->getSystemInfo('country', 'ID');
        $this->cid = $this->getSystemInfo('cid', 'beter-indobet-stage');
        $this->partner_secret = $this->getSystemInfo('partner_secret', '41127a6f201749eb3bad7aedc62e577e6369de54');
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;
        $this->conversion_rate = $this->getSystemInfo('conversion_rate', 0);
        $this->current_domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}";
        $this->use_default_home_url = $this->getSystemInfo('use_default_home_url', false);
		$this->default_home_url = $this->getSystemInfo('default_home_url', $this->current_domain);     
	}

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode() {
        return BETER_SPORTS_SEAMLESS_GAME_API;
    }

    public function generateUrl($apiName, $params) {
        return $this->api_url;
    }

    protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        return $errCode || intval($statusCode, 10) >= 503;
    }

    protected function customHttpCall($ch, $params) {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params["main_params"]));
	}

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create account for Betsy Seamless";
        if($return){
            $success = true;
            $message = "Successfull create account for Betsy Seamless";
        }

        return array("success" => $success, "message" => $message);
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
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $token = $this->getPlayerToken($playerId);

        $lang = isset($extra['lang']) ? $extra['lang'] : $this->lang;
        $game_code = isset($extra['game_code']) ? "&launchalias=".$extra['game_code'] : '';
       
        if (array_key_exists("extra", $extra)) {
            
			//extra checking for home link
			if ($this->use_default_home_url) {
				if(isset($extra['home_link']) && !empty($extra['home_link'])) {
					$this->default_home_url = $extra['home_link'];
				}
			}

            //extra checking for home link
            if(isset($extra['extra']['home_link']) && !empty($extra['extra']['home_link'])) {
                $this->default_home_url = $extra['extra']['home_link'];
            }
        }

        $homepage = $this->default_home_url;
        $funMode = ($extra['game_mode'] == 'demo') ? 1 : 0;

        $url = $this->api_url."?locale=".$lang."&cid=".$this->cid."&token=".$token;
			
        return array("success" => true, "url" => $url);
    }

    public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en-us':
            case 'en_us':
            case 'EN':
            case 'en':
                $lang = 'en'; 
                break;
            case Language_function::INT_LANG_THAI:
            case 'th':
                $lang = 'th'; 
                    break;
            case Language_function::INT_LANG_CHINESE:
            case 'zh-cn':
            case 'zh_cn':
            case 'ZH':
            case 'zh':
            case 'zh-CN':
                $lang = 'zh'; 
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
                $lang = 'id'; 
                break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt':
                $lang = 'pt'; 
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $apiName = '') {
        $success = false;

        $this->CI->utils->debug_log('BETSY : (' . __FUNCTION__ . ')', $resultArr);

        if(isset($resultArr['ErrorCode']) && $resultArr['ErrorCode'] == self::ERROR_CODE_SUCCESS['ErrorCode']){
            $success = true;
        }else{
            $this->setResponseResultToError($responseResultId);
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

        $sqlTime='transaction.updated_at >= ? and transaction.updated_at <= ?';

        if($use_bet_time) {
            $sqlTime='transaction.start_at >= ? and transaction.start_at <= ?';
        }
        $sql = <<<EOD
SELECT
    transaction.id as sync_index,
    transaction.amount as amount,
    transaction.bet_amount,
    transaction.result_amount,
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

    transaction.player_id,

    game_description.id as game_description_id,
    game_description.game_name as game_description_name,
    game_description.game_code as game_code,
    game_description.game_type_id
FROM
    {$this->original_transaction_table} as transaction
    LEFT JOIN game_description ON transaction.game_id = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE
transaction_type != "unsettle" and transaction_type != "rollback" and {$sqlTime} and transaction.game_platform_id = ?

EOD;

        $params = [
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

        $bet_amount = isset($row['bet_amount']) ? $this->gameAmountToDBTruncateNumber($row['bet_amount']) : 0;
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
                'player_username'       => null
            ],
            'amount_info' => [
                'bet_amount'            => $bet_amount,
                'result_amount'         => $this->gameAmountToDBTruncateNumber($row['result_amount']),
                'bet_for_cashback'      => $bet_amount,
                'real_betting_amount'   => $bet_amount,
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $this->gameAmountToDBTruncateNumber($row['after_balance']),
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

        $this->utils->debug_log('Beter ', $data);
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
            case 'settle':
                $row['status'] = Game_logs::STATUS_SETTLED;
                break;
            case 'rollback':
                $row['note'] = 'Cancelled';
                $row['status'] = Game_logs::STATUS_CANCELLED;
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
FROM {$this->original_transaction_table} as t
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

                    $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;

                    if($transaction['trans_type'] == self::TRANSACTION_TYPE_BET){
                        $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                    }/* elseif($transaction['trans_type'] == self::TRANSACTION_TYPE_CANCEL || $transaction['trans_type']==self::TRANSACTION_TYPE_END){
                        $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                    } */

                    $temp_game_records[] = $temp_game_record;
                    unset($temp_game_record);
                }
            }

            $transactions = $temp_game_records;

            $this->CI->utils->debug_log('Beter: (' . __FUNCTION__ . ')', 'transactions:', $transactions);
        }
}