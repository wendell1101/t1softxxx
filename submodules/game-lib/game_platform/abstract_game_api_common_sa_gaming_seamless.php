<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/game_api_sagaming.php';

/**
    * API NAME: SA_GAMING_SEAMLESS - 5691
    * Hash: DES-CBC
    * Wallet Type: Seamless
    * Ticket No: OGP-16449
    *
    * @category Game_platform
    * @version not specified
    * @copyright 2013-2022 tot
    * @author   Pedro P. Vitor Jr.
 */

abstract class Abstract_game_api_common_sa_gaming_seamless extends Game_api_sagaming {

    private $api_url;
    private $encrypt_key;
    private $key;
    private $iv;
    
    protected $currency;
    protected $game_url;
    protected $md5_key;
    protected $fix_check_key;
    protected $secret_key;
    public $add_prefix_in_round_id;

    public $use_sa_gaming_wallet_transaction;

	public function __construct() {
		parent::__construct();

        $this->api_url = $this->getSystemInfo('url','http://sai-api.sa-apisvr.com/api/api.aspx');
        $this->secret_key = $this->getSystemInfo('secret_key', '27AB2A7C3E5F418CA18506156F7F2F82'); 
        $this->md5_key = $this->getSystemInfo('md5_key', 'GgaIMaiNNtg'); 

        $this->encrypt_key = $this->getSystemInfo('encrypt_key', 'g9G16nTs'); 
        $this->app_encrypt_key = $this->getSystemInfo('app_encrypt_key', 'M06!1OgI'); 

        $this->game_url = $this->getSystemInfo('game_url', 'https://www.sai.slgaming.net/app.aspx');
        $this->backoffice_url = $this->getSystemInfo('backoffice_url');
		$this->lobby_code = $this->getSystemInfo('lobby_code', 'A2343');
        $this->currency = $this->getSystemInfo('currency', 'THB');

        $this->country_code = $this->getSystemInfo('country_code', 'TH');
        $this->language = $this->getSystemInfo('language', 'th-th');
        $this->is_redirect = $this->getSystemInfo('is_redirect', false);

        $this->use_sa_gaming_wallet_transaction = $this->getSystemInfo('use_sa_gaming_wallet_transaction', true); // OGP-28645
        $this->original_transaction_table = $this->getSystemInfo('original_transaction_table', 'sa_gaming_seamless_wallet_transactions');
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', false);
        $this->add_prefix_in_round_id = $this->getSystemInfo('add_prefix_in_round_id', true);

        //$this->CI->load->model(['sagaming_game_logs', 'game_provider_auth', 'original_game_logs_model', 'response_result', 'game_description_model', 'player_model', 'common_token']);
		$this->CI->load->model(['game_provider_auth', 'original_game_logs_model', 'response_result', 'game_description_model', 'player_model', 'common_token']);	
        
    }

    // const ORIGINAL_LOGS_TABLE_NAME = 'sa_gaming_seamless_game_logs';

    public function isSeamLessGame()
    {
       return true;
    }

    public function getPlatformCode() {
        return SA_GAMING_SEAMLESS_API;
    }

	private function DES($key, $iv=0 ) {
		$this->key = $key;
        if( $iv == 0 ) {
            $this->iv = $key;
        } else {
            $this->iv = $iv;
		}
    }

	private function encrypt($str) {
		return base64_encode(openssl_encrypt($str, 'DES-CBC', $this->key, OPENSSL_RAW_DATA, $this->iv));
	}

	private function decrypt($str) {
		$str = openssl_decrypt(base64_decode($str), 'DES-CBC', $this->key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $this->iv);
		return rtrim($str, "\x01..\x1F");

	}
	
	public function strEncrypt($params) {
        $this->utils->debug_log('<--------------- SA_GAMING_SEAMLESS API ------------> strEncrypt-params: ', $params);
		$this->DES($this->encrypt_key);
		$params = http_build_query($params);

        $this->utils->debug_log('<--------------- SA_GAMING_SEAMLESS API ------------> strEncrypt-http_build_query-params: ', $params);
		
        $enc = $this->encrypt($params); 
		$str = urlencode($this->encrypt($params));
		
		return $str;
	}

	public function strDecrypt($params) {
		$this->DES($this->encrypt_key);				
		$str = $this->decrypt(urldecode($params));
		
		return $str;
	}

    public function queryPlayerBalance($userName) {
		$playerId = $this->CI->player_model->getPlayerIdByUsername($userName);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

		$result = array(
			'success' => true, 
			'balance' => $balance
		);

		$this->utils->debug_log('<--------------- SA_GAMING_SEAMLESS API ------------> Query Player Balance: ', $result);
		
		return $result;
	}

	public function depositToGame($userName, $amount, $transfer_secure_id=null){
		
		$external_transaction_id = $transfer_secure_id;
		
		if(empty($external_transaction_id)){
			$external_transaction_id = $this->utils->getTimestampNow();
		}
		return array(
			'success' => true,
			'external_transaction_id' => $external_transaction_id,
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
			'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
		);
	}

    public function withdrawFromGame($userName, $amount, $transfer_secure_id=null,$notRecordTransaction=false) {
    	
		$playerBalance = $this->queryPlayerBalance($userName);
		
		if(empty($transfer_secure_id)){
			$external_transaction_id = $this->utils->getTimestampNow();
		}

		return array(
			'success' => true,
			'external_transaction_id' => $external_transaction_id,
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
			'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
		);
    }

    function syncMergeToGameLogs($token)
    {
        $enabled_game_logs_unsettle=false;
        if ($this->use_sa_gaming_wallet_transaction) {
            $enabled_game_logs_unsettle=true;
            if ($this->enable_merging_rows) {
                return $this->commonSyncMergeToGameLogs(
                    $token,
                    $this,
                    [$this, 'queryOriginalGameLogsFromTransMerge'],
                    [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTransMerge'],
                    [$this, 'preprocessOriginalRowForGameLogsMerge'],
                    $enabled_game_logs_unsettle
                );
            } else {
                return $this->commonSyncMergeToGameLogs(
                    $token,
                    $this,
                    [$this, 'queryOriginalGameLogsFromTrans'],
                    [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
                    [$this, 'preprocessOriginalRowForGameLogsFromTrans'],
                    $enabled_game_logs_unsettle
                );
            }
        } else {
            return $this->commonSyncMergeToGameLogs(
                $token,
                $this,
                [$this, 'queryOriginalGameLogs'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
                [$this, 'preprocessOriginalRowForGameLogs'],
                $enabled_game_logs_unsettle
            );
        }
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        $sqlTime='`sag`.`PayoutTIme` >= ?
          AND `sag`.`PayoutTIme` <= ? AND sag.PlayerId IS NOT NULL';
        if($use_bet_time){
            $sqlTime='`sag`.`BetTime` >= ?
          AND `sag`.`BetTime` <= ? AND sag.PlayerId IS NOT NULL';
        }

        $sql = <<<EOD
            SELECT
                sag.id as sync_index,
                sag.PlayerId as player_id,
                sag.UserName as username,
                sag.external_uniqueid,
                sag.BetTime as start_at,
                sag.PayoutTime as end_at,
                sag.BetTime as bet_at,
                sag.PayoutTime as updated_at,
                sag.GameType as game_type,
                sag.extGameCode as game_code,
                sag.extGameCode as game_name,
                sag.response_result_id,
                sag.ResultAmount as result_amount,
                sag.BetAmount,
                sag.GameID as round,
                sag.GameID,
                sag.BetID,
                sag.BetType,
                sag.GameType,
                sag.extra,
                sag.md5_sum,
                sag.Balance,
                sag.Rolling,
                gd.id as game_description_id,
                gd.game_name as game,
                gd.game_type_id,
                gd.void_bet,
                gt.game_type,
                csw.before_balance as before_balance
            FROM {$this->original_gamelogs_table} as sag
            LEFT JOIN game_description as gd ON sag.extGameCode = gd.game_code AND gd.void_bet != 1 AND gd.game_platform_id =?
            LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
            LEFT JOIN common_seamless_wallet_transactions as csw ON csw.round_id = sag.GameId AND csw.game_platform_id =? and csw.transaction_id = sag.TransactionID and sag.PlayerId = csw.player_id
            WHERE
            {$sqlTime}
EOD;
$this->CI->utils->info_log('<===== SAGAMING syncmerge', $sqlTime, $dateFrom, $dateTo, $this->original_gamelogs_table, $sql, $this->getPlatformCode());
        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function queryTransactionByDateTime($startDate, $endDate){

$sql = <<<EOD
SELECT 
t.player_id as player_id,
t.start_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_unique_id as external_uniqueid,
t.transaction_type trans_type,
t.extra_info extra_info,
t.transaction_id
FROM {$this->original_transaction_table} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ? 
ORDER BY t.updated_at asc;

EOD;

$params=[$this->getPlatformCode(),$startDate, $endDate];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function processTransactions(&$transactions){
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        $temp_game_records = [];

        if(!empty($transactions)){
            foreach($transactions as $transaction){
                $transaction_id = isset($transaction['transaction_id']) ? $transaction['transaction_id'] : null;
                $transaction_date = $this->CI->original_seamless_wallet_transactions->getSpecificField($this->original_gamelogs_table, 'BetTime', ['TransactionID' => $transaction_id]);

                if (empty($transaction_date)) {
                    $transaction_date = !empty($transaction['transaction_date']) ? $this->gameTimeToServerTime($transaction['transaction_date']) : null;
                }

                $temp_game_record = [];
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction_date;
                $temp_game_record['amount'] = abs($transaction['amount']);
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];

                if(empty($temp_game_record['round_no']) && !empty($transaction_id)){
                    $temp_game_record['round_no'] = $transaction_id;
                }

                //$extra_info = @json_encode($transaction['extra_info'], true);
                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                if(isset($transaction['note']) && !empty($transaction['note'])){
                    $extra['note'] = $transaction['note'];
                }

                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;

                if($transaction['trans_type'] == 'bet'){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                if(isset($transaction['transaction_type'])){
                    $temp_game_record['transaction_type'] = $transaction['transaction_type'];
                }
                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }


    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime='transaction.updated_at BETWEEN ? AND ?';

        if($use_bet_time) {
            $sqlTime='transaction.start_at BETWEEN ? AND ?';
        }

        $md5Fields = implode(", ", array('transaction.amount', 'transaction.after_balance', 'transaction.round_id', 'transaction.game_id', 'transaction.start_at', 'transaction.end_at', 'transaction.updated_at'));

        $sql = <<<EOD
SELECT
    game_description.game_type_id,
    game_description.id AS game_description_id,
    transaction.game_id,
    game_description.english_name AS game,

    transaction.player_id,

    transaction.amount as bet_amount, 
    transaction.amount as result_amount,
    transaction.after_balance,

    transaction.start_at,
    transaction.end_at,
    transaction.updated_at,

    transaction.status,
    transaction.external_unique_id as external_uniqueid,
    transaction.round_id,
    MD5(CONCAT({$md5Fields})) AS md5_sum,
    transaction.response_result_id,
    transaction.id as sync_index,
    transaction.transaction_id,
    transaction.reference_transaction_id,
    transaction.transaction_type

FROM
    {$this->original_transaction_table} as transaction
    LEFT JOIN game_description ON transaction.game_id = game_description.external_game_id AND game_description.game_platform_id = ?

WHERE
    transaction.game_platform_id = ? AND
    {$sqlTime}
EOD;

            $params = [
                $this->getPlatformCode(),
                $this->getPlatformCode(),
                $dateFrom,
                $dateTo,
            ];

            $this->CI->utils->debug_log(__METHOD__ . ' ===========================> sql and params - ' . __LINE__, $sql, $params);
            $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
            return $result;
    }

    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row)
    {
        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_id'],
                'game_type'             => null,
                'game'                  => $row['game']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => null,
            ],
            'amount_info' => [
                'bet_amount'            => isset($row['valid_bet_amount']) ? $row['valid_bet_amount'] : $row['bet_amount'],
                'result_amount'         => $row['result_amount'],
                'bet_for_cashback'      => isset($row['valid_bet_amount']) ? $row['valid_bet_amount'] : $row['bet_amount'],
                'real_betting_amount'   => isset($row['real_bet_amount']) ? $row['real_bet_amount'] : $row['bet_amount'],
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
            // 'status' => Game_logs::STATUS_SETTLED,
            'status' => $this->getSbeStatus($row['status'], $row['transaction_type']),
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => $this->preprocessBetDetails($row,null,true),
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $data;
    }

    private function getSbeStatus($transStatus, $transType){
        switch (strtolower($transStatus)) {
            case 'ok':
                $status = Game_logs::STATUS_SETTLED;
                break;
            case 'waiting':
                $status = Game_logs::STATUS_PENDING;
                break;
            case 'cancelled':
                $status = Game_logs::STATUS_REFUND;
                break;
            default:
                $status = Game_logs::STATUS_PENDING;
                break;
        }

        if ($transType == 'cancel') {
            $status = Game_logs::STATUS_REFUND;
        }

        return $status;
    }


    public function preprocessOriginalRowForGameLogsFromTrans(array &$row)
    {
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        #set bet and result amount
        /* if ($row['transaction_type'] == 'bet') {
            $row['result_amount'] = -$row['bet_amount'];
            $row['bet_amount'] = abs($row['bet_amount']);
        } else {
            $row['result_amount'] = $row['bet_amount'];
            $row['bet_amount'] = 0;
        } */

        $result = $this->getWinLoseResult($row);

        if ($row['transaction_type'] == 'bet') {
            $row['bet_amount'] = $result['valid_bet_amount'];
            $row['real_bet_amount'] = $result['real_bet_amount'];
            $row['result_amount'] = -$result['valid_bet_amount'];
        } else {
            $row['result_amount'] = $result['result_amount'] >= 0 ? $result['result_amount'] + $result['valid_bet_amount'] : 0;
            $row['bet_amount'] = 0;
        }

        if ($this->add_prefix_in_round_id) {
            if ($row['status'] == 'cancelled' || $row['transaction_type'] == 'cancel') {
                $row['round_id'] = $this->utils->mergeArrayValues(['cancelled', $row['round_id']]);
            }
        }
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_type_id = null;

        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if (empty($game_description_id)) {
            $game_description_id = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(), $unknownGame->game_type_id, $row['game_id'], $row['game_id']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }

    public function queryOriginalGameLogsFromTransMerge($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime='transaction.updated_at BETWEEN ? AND ?';

        if($use_bet_time) {
            $sqlTime='transaction.start_at BETWEEN ? AND ?';
        }

        $md5Fields = implode(", ", array('transaction.amount', 'transaction.after_balance', 'transaction.round_id', 'transaction.game_id', 'transaction.start_at', 'transaction.end_at', 'transaction.updated_at'));

        $sql = <<<EOD
SELECT
    game_description.game_type_id,
    game_description.id AS game_description_id,
    transaction.game_id,
    game_description.english_name AS game,

    transaction.player_id,

    SUM(CASE WHEN transaction_type = 'bet' THEN amount ELSE 0 END) AS bet_amount,
    SUM(CASE WHEN transaction.transaction_type = 'win' THEN transaction.amount ELSE 0 END) -
    SUM(CASE WHEN transaction.transaction_type IN ('bet', 'cancel') THEN transaction.amount ELSE 0 END) AS result_amount,
    transaction.before_balance,
    transaction.after_balance,

    transaction.start_at,
    transaction.end_at,
    transaction.updated_at,

    transaction.status,
    transaction.external_unique_id as external_uniqueid,
    transaction.round_id,
    MD5(CONCAT({$md5Fields})) AS md5_sum,
    transaction.response_result_id,
    transaction.id as sync_index,

    transaction.transaction_type,

    CASE WHEN COUNT(CASE WHEN transaction_type IN ('win','lose','cancel') THEN 1 END) > 0 THEN 1 ELSE 0 END AS settled

FROM
    {$this->original_transaction_table} as transaction
    LEFT JOIN game_description ON transaction.game_id = game_description.external_game_id AND game_description.game_platform_id = ?

WHERE
    transaction.game_platform_id = ? AND {$sqlTime}
    GROUP BY round_id;
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
        ];

        $this->CI->utils->debug_log(__METHOD__ . ' ===========================> sql and params - ' . __LINE__, $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function makeParamsForInsertOrUpdateGameLogsRowFromTransMerge(array $row)
    {
        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_id'],
                'game_type'             => null,
                'game'                  => $row['game']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => null
            ],
            'amount_info' => [
                'bet_amount'            => isset($row['valid_bet_amount']) ? $row['valid_bet_amount'] : $row['bet_amount'],
                'result_amount'         => $row['result_amount'],
                'bet_for_cashback'      => isset($row['valid_bet_amount']) ? $row['valid_bet_amount'] : $row['bet_amount'],
                'real_betting_amount'   => isset($row['real_bet_amount']) ? $row['real_bet_amount'] : $row['bet_amount'],
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
            'status' => $row['settled'] ? Game_logs::STATUS_SETTLED : Game_logs::STATUS_PENDING,
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => $this->preprocessBetDetails($row, null, true),
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $data;
    }

    public function preprocessOriginalRowForGameLogsMerge(array &$row)
    {
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $result = $this->getWinLoseResult($row);

        $row['bet_amount'] = $result['valid_bet_amount'];
        $row['real_bet_amount'] = $result['real_bet_amount'];
        $row['result_amount'] = $result['result_amount'];
    }

    public function syncReferenceTransactionId($dateTimeFrom, $dateTimeTo) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);

        $transactions = $this->CI->original_seamless_wallet_transactions->queryPlayerTransactionsCustom($this->original_transaction_table, "(created_at BETWEEN '{$dateTimeFrom}' AND '{$dateTimeTo}') AND transaction_type != 'bet'");
        $reference_transaction_id = null;

        $result = [
            'updated' => 0,
        ];

        foreach ($transactions as $transaction) {
            $extra_info = !empty($transaction['extra_info']) ? json_decode($transaction['extra_info'],  true) : [];
            
            if (!empty($extra_info['txn_reverse_id'])) {
                $reference_transaction_id = $extra_info['txn_reverse_id'];
            }

            if (!empty($extra_info['payoutdetails'])) {
                $payout_details = json_decode($extra_info['payoutdetails'], true);
                $reference_transaction_id = !empty($payout_details['betlist'][0]['txnid']) ? $payout_details['betlist'][0]['txnid'] : null;
            }

            if (empty($transaction['reference_transaction_id']) && !empty($reference_transaction_id)) {
                $where = [
                    'external_unique_id' => $transaction['external_unique_id'],
                ];

                $data = [
                    'reference_transaction_id' => $reference_transaction_id,
                ];

                $is_updated = $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->original_transaction_table, $where, $data);

                if ($is_updated) {
                    $result['updated']++;
                }
            }

            $reference_transaction_id = null;
        }

        return $result;
    }

    /* public function getWinLoseResult__($player_id, $round_id, $extra = []) {
        $result = [
            'valid_bet_amount' => isset($extra['bet_amount']) ? $extra['bet_amount'] : 0,
            'real_bet_amount' => isset($extra['bet_amount']) ? $extra['bet_amount'] : 0,
            'result_amount' => isset($extra['result_amount']) ? $extra['result_amount'] : 0,
        ];

        $this->CI->load->model(['original_seamless_wallet_transactions']);
        // get results from extra info
        $transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_transaction_table, "(player_id = {$player_id} AND round_id = '{$round_id}' AND transaction_type IN ('win', 'lose'))");

        if (!empty($transaction)) {
            $extra_info = !empty($transaction['extra_info']) ? json_decode($transaction['extra_info'], true) : [];
            $payout_details = !empty($extra_info['payoutdetails']) ? json_decode($extra_info['payoutdetails'], true) : [];

            if (!empty($payout_details['betlist'])) {
                foreach ($payout_details['betlist'] as $payout_detail) {
                    if (isset($payout_detail['rolling'])) {
                        $result['valid_bet_amount'] = 0;
                        $result['valid_bet_amount'] += abs($payout_detail['rolling']);
                    }

                    if (isset($payout_detail['betamount'])) {
                        $result['real_bet_amount'] = 0;
                        $result['real_bet_amount'] += abs($payout_detail['betamount']);
                    }

                    if (isset($payout_detail['resultamount'])) {
                        $result['result_amount'] = 0;
                        $result['result_amount'] += $payout_detail['resultamount'];
                    }
                }
            }

            if (!empty($extra_info['gametype']) && $extra_info['gametype'] == 'pokdeng') {
                // to fix data issue of pokdeng
                // if the result is negative amount and the absolute amount is greater than bet amount, set the real bet amount same with result amount.
                if ($result['result_amount'] < 0 && abs($result['result_amount']) > abs($result['valid_bet_amount'])) {
                    $result['real_bet_amount'] = abs($result['result_amount']);
                }
            }
        }

        return $result;
    } */

    public function getWinLoseResult($row) {
        $result = [
            'valid_bet_amount' => 0,
            'real_bet_amount' => 0,
            'result_amount' => 0,
        ];

        $this->CI->load->model(['original_seamless_wallet_transactions']);
        // get results from extra info
        $transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_transaction_table, "(player_id = {$row['player_id']} AND round_id = '{$row['round_id']}' AND transaction_type IN ('win', 'lose'))");

        if (!empty($transaction)) {
            $extra_info = !empty($transaction['extra_info']) ? json_decode($transaction['extra_info'], true) : [];
            $payout_details = !empty($extra_info['payoutdetails']) ? json_decode($extra_info['payoutdetails'], true) : [];

            if (!empty($payout_details['betlist'])) {
                foreach ($payout_details['betlist'] as $payout_detail) {
                    if (isset($payout_detail['rolling'])) {
                        $result['valid_bet_amount'] += abs($payout_detail['rolling']);
                    }

                    if (isset($payout_detail['betamount'])) {
                        $result['real_bet_amount'] += abs($payout_detail['betamount']);
                    }

                    if (isset($payout_detail['resultamount'])) {
                        $result['result_amount'] += $payout_detail['resultamount'];
                    }
                }
            }

            if (!empty($extra_info['gametype']) && $extra_info['gametype'] == 'pokdeng') {
                // to fix data issue of pokdeng
                // if the result is negative amount and the absolute amount is greater than bet amount, set the real bet amount same with result amount.
                if ($result['result_amount'] < 0 && abs($result['result_amount']) > abs($result['valid_bet_amount'])) {
                    $result['real_bet_amount'] = abs($result['result_amount']);
                }
            }
        } else {
            $result = [
                'valid_bet_amount' => isset($row['bet_amount']) ? $row['bet_amount'] : 0,
                'real_bet_amount' => isset($row['bet_amount']) ? $row['bet_amount'] : 0,
                'result_amount' => isset($row['result_amount']) ? $row['result_amount'] : 0,
            ];
        }

        return $result;
    }
}
/*end of file*/
