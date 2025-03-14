<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_game_logs_model.php';

class Ruby_play_transactions extends Base_game_logs_model {
	
	function __construct() {
		parent::__construct();
	}

	protected $tableName = "ruby_play_transactions";

	public function isTransactionIdAlreadyExists($transaction_id,$action) {

		$this->db->from($this->tableName)
			->where('transactionId', $transaction_id)
				->where('action', $action);
		return $this->runExistsResult();

	}

	public function isRefTransactionIdAlreadyExists($transaction_id,$action) {

		$this->db->from($this->tableName)
			->where('referenceTransactionId', $transaction_id)
				->where('action', $action);
		return $this->runExistsResult();

	}

	public function insertTransaction($data) {
       $this->db->insert($this->tableName,$data);

       return $this->db->affected_rows();
    }

	public function getGameLogStatistics($dateFrom, $dateTo) {
		return null;
	}

	public function isRowByTransactionId($transaction_id) {
		$qry = $this->db->get_where($this->tableName, array('transactionId' => $transaction_id))->row_array();
		return $qry;
	}

	public function isRowByActionTransactionId($transaction_id,$action) {
		$qry = $this->db->get_where($this->tableName, array('transactionId' => $transaction_id, 'action' => $action))->row_array();
		return $qry;
	}

	public function isRowByRoundId($roundId) {
		$qry = $this->db->get_where($this->tableName, array('roundId' => $roundId));
		return $this->getMultipleRowArray($qry);
	}

	public function isRowByRefTransactionId($ref_transid) {
		$qry = $this->db->get_where($this->tableName, array('referenceTransactionId' => $ref_transid))->row_array();
		return $qry;
	}


}