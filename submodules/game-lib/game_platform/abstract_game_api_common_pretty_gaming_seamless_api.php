<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/abstract_game_api_common_pretty_gaming_api.php';
/**
* Game Provider: Booming games
* Game Type: Slots
* Wallet Type: Seamless
*
/**
* API NAME: BOOMING
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @renz.php.ph
**/

abstract class Abstract_game_api_common_pretty_gaming_seamless_api extends Abstract_game_api_common_pretty_gaming_api {

	# Fields in fg_seamless_gamelogs we want to detect changes for update
    const MD5_FIELDS_FOR_TRANSACTION_LOGS = [
        'playerUsername',
        'ticketId',
        'type',
        'currency',
        'gameId',
        'totalBetAmt',
        'totalPayOutAmt',
        'winLoseTurnOver',
        'txtList',
        'createDate',
        'requestDate',
        'status'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_TRANSACTION_LOGS = [
        'totalBetAmt',
        'totalPayOutAmt',
        'winLoseTurnOver'
    ];

	public function __construct() {
		parent::__construct();
	}

    public function isSeamLessGame()
    {
       return true;
    }

	// public function queryPlayerBalance($gameUsername)
	// {
    //     $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
	// 	$balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

	// 	$result = array(
	// 		'success' => true,
	// 		'balance' => $this->dBtoGameAmount($balance)
	// 	);

	// 	return $result;
	// }

    public function queryPlayerBalance($playerName) { // need to overwrite the queryPlayerBalance because its extends to transfer class

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        $this->utils->debug_log(__FUNCTION__,'PRETTY Gaming (Query Player Balance): ', $result);

        return $result;

    }

    public function queryPlayerBalanceByGameUsername($gameUsername)
	{
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

		$result = array(
			'success' => true,
			'balance' => $balance
		);

		return $result;
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null)
	{
		$external_transaction_id = $transfer_secure_id;

	    return array(
	        'success' => true,
	        'external_transaction_id' => $external_transaction_id,
	        'response_result_id ' => NULL,
	        'didnot_insert_game_logs'=>true,
	    );
	}

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null,$notRecordTransaction=false)
    {
		$external_transaction_id = $transfer_secure_id;

	    return array(
	        'success' => true,
	        'external_transaction_id' => $external_transaction_id,
	        'response_result_id ' => NULL,
	        'didnot_insert_game_logs'=>true,
	    );
    }

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
    }

	public function doSyncOriginal($data, $table_name, $process) {
        $success = false;
		$result = ['data_count' => 0];

		$md5_fields = self::MD5_FIELDS_FOR_GAME_LOGS;
		$md5_float = self::MD5_FLOAT_AMOUNT_FIELDS_FOR_GAME_LOGS;

		if ($process == self::TRANSACTION_LOGS) {
			$md5_fields = self::MD5_FIELDS_FOR_TRANSACTION_LOGS;
			$md5_float = self::MD5_FLOAT_AMOUNT_FIELDS_FOR_TRANSACTION_LOGS;
		}

        if (!empty($data)) {
            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $table_name,
                $data,
                'external_uniqueid',
                'external_uniqueid',
                $md5_fields,
                'md5_sum',
                'id',
                $md5_float
            );

			// $this->CI->utils->debug_log('after process available rows', !empty($gameRecords) ? count($gameRecords) : 0, !empty($insertRows) ? count($insertRows) : 0, !empty($updateRows) ? count($updateRows) : 0);

            unset($data);

            if (!empty($insertRows))
            {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert', [], $table_name);
                $success = true;
            }
            unset($insertRows);

            if (!empty($updateRows))
            {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update', [], $table_name);
                $success = true;
            }
            unset($updateRows);
        }
        return array('success' => $success);
	}

    public function queryTransactionByDateTime($startDate, $endDate){
        $this->CI->load->model(array('original_game_logs_model'));

$sql = <<<EOD
SELECT
gpa.player_id as player_id,
t.created_at transaction_date,
t.totalBetAmt as bet_amount,
t.totalPayOutAmt as payout_amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.ticketId as round_no,
t.external_uniqueid as external_uniqueid,
t.api_request trans_type
FROM {$this->original_transaction_table} as t
JOIN game_provider_auth gpa on gpa.login_name = t.playerUsername
WHERE `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;

$params=[$startDate, $endDate];

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
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];
                $extra_info = [];
                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $extra['extra'] = $extra_info;
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                $temp_game_record['amount'] = abs($transaction['payout_amount']);
                if(in_array($transaction['trans_type'], ['UserPlaceBet'])){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                    $temp_game_record['amount'] = abs($transaction['bet_amount']);
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }



}
/*end of file*/
