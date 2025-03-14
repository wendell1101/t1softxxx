<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_game_logs_model.php';

class Sexy_baccarat_transactions extends Base_game_logs_model {
	
	function __construct() {
		parent::__construct();
	}

	protected $tableName = "sexy_baccarat_transactions";

	public function isTransactionIdAlreadyExists($transaction_id) {

		$this->db->from($this->tableName)
			->where('platformTxId', $transaction_id);

		return $this->runExistsResult();

	}

	public function insertTransaction($data) {
    	// print_r($data);exit;
       $this->db->insert($this->tableName,$data);

       return $this->db->affected_rows();
    }

	public function getGameLogStatistics($dateFrom, $dateTo) {
		return null;
	}

	public function getTransactionRecord($transaction_id, $type, $where=null) {
		$condition = array('platformTxId' => $transaction_id, 'action' => $type);
		
		if(!empty($where) && is_array($where)){
			$condition = array_merge($condition, $where);
		}

		$qry = $this->db->get_where($this->tableName, $condition)->row_array();
		return $qry;
	}

	public function isRowByTransactionId($transaction_id) {
		$qry = $this->db->get_where($this->tableName, array('platformTxId' => $transaction_id))->row_array();
		return $qry;
	}

	public function isRowByBetTransactionId($transaction_id) {
		$qry = $this->db->get_where($this->tableName, array('platformTxId' => $transaction_id, 'action' => 'bet'))->row_array();
		return $qry;
	}

	public function isRowByCancelledTransactionId($transaction_id) {
		$qry = $this->db->get_where($this->tableName, array('platformTxId' => $transaction_id, 'action' => 'cancelBet'))->row_array();
		return $qry;
	}

	public function isRowByVoidedTransactionId($transaction_id) {
		$qry = $this->db->get_where($this->tableName, array('platformTxId' => $transaction_id, 'action' => 'voidSettle'))->row_array();
		return $qry;
	}

	public function isBetSettleIdExist($transaction_id) {
		$array = array('platformTxId' => $transaction_id, 'action !=' => 'settle');
		$this->db->from($this->tableName)
			->where($array);

		return $this->runExistsResult();
	}

	public function isRowBySettleId($transaction_id) {
		$qry = $this->db->get_where($this->tableName, array('platformTxId' => $transaction_id, 'action' => 'settle'))->row_array();
		return $qry;
	}

	public function isSettleIdExist($transaction_id) {
		$this->db->from($this->tableName)
			->where('platformTxId', $transaction_id)
			->where('action', 'settle');

		return $this->runExistsResult();
	}

	public function isUnsettleIdExist($transaction_id) {
		$this->db->from($this->tableName)
			->where('platformTxId', $transaction_id)
			->where('action', 'unsettle');

		return $this->runExistsResult();
	}

	public function isExternalUniqueExist($external_uniqueid) {
		$this->db->from($this->tableName)
			->where('external_uniqueid', $external_uniqueid);
		return $this->runExistsResult();
	}

	public function updateTransactionData($transaction_id, $type, $data){
		$this->db->where('platformTxId', $transaction_id);
		$this->db->where('action', $type);
		$this->db->set($data);
		return $this->runAnyUpdate($this->tableName);
	}

	public function updateCancelBetTransactionData($transaction_id, $data){
		$this->db->where('platformTxId', 'cancelBet-'.$transaction_id);
		$this->db->where('action', 'cancelBet');
		$this->db->set($data);
		return $this->runAnyUpdate($this->tableName);
	}

    

	public function getAllRelatedTransactions($transaction_id, $player_id){
        //$this->db->where('player_id', $player_id);
        $this->db->where('group_transaction_id', $transaction_id);
        $this->db->from($this->tableName);
        $qry = $this->db->get();
		return $qry->result_array();
	}

	public function isSettleExists($transaction_id, $status) {
		$this->db->from($this->tableName)
			->where('group_transaction_id', $transaction_id)
			->where('action', 'settle')
			->where('action_status', $status);

		return $this->runExistsResult();
	}

	public function isBetExists($transaction_id, $status) {
		$this->db->from($this->tableName)
			->where('group_transaction_id', $transaction_id)
			->where('action', 'bet')
			->where('action_status', $status);

		return $this->runExistsResult();
	}

	public function isVoidBetExists($transaction_id, $status) {
		$this->db->from($this->tableName)
			->where('group_transaction_id', $transaction_id)
			->where('action', 'voidBet')
			->where('action_status', $status);

		return $this->runExistsResult();
	}


}