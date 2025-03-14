<?php
require_once dirname(__FILE__) . '/base_model.php';

class Seamless_balance_history extends BaseModel {

	protected $tableName = 'seamless_balance_history';
    
	function __construct() {
		parent::__construct();
	}

	public function setTableName($table){
		$this->tableName = $table;

	}

	public function saveSeamlessBalanceHistoryDeclineWithdrawal($playerId, $beforeBalance, $afterBalance, $walletAccountId){
		$sql = "SELECT w.playerId, w.amount, w.dwStatus, w.transactionCode FROM walletaccount w WHERE w.walletAccountId = ? LIMIT 1";
		$query = $this->db->query($sql, array($walletAccountId));
		$record = $query->result()[0];
		$insertData = [];
		$insertData['player_id'] = $playerId;
		$insertData['transaction_date'] = date("Y-m-d H:i:s");
		$insertData['amount'] = $record->amount;
		$insertData['before_balance'] = $beforeBalance;
		$insertData['after_balance'] = $afterBalance;
		$insertData['round_no'] = $record->transactionCode;
		$insertData['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
		//$insertData['transaction_status'] = 'withdrawal';
		$insertData['external_uniqueid'] = 'DECLINE-'.$record->transactionCode;
		$extraInfo = ['trans_type'=>'withdrawal','note'=>'declined withdrawal'];
		$insertData['extra_info'] = json_encode($extraInfo);
		$this->utils->debug_log("bermar saveSeamlessBalanceHistoryDeclineWithdrawal", $insertData);
		return $this->saveSeamlessBalanceTransaction($insertData);
	}

	public function saveSeamlessBalanceTransaction($data){
		return $this->insertData($this->tableName, $data);
	}

}

/////end of file///////