<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_game_logs_model.php';

class Betgames_wallet_transactions extends Base_game_logs_model {
	
	function __construct() {
		parent::__construct();
	}

	protected $tableName = "betgames_wallet_transactions";

	public function isTransactionIdAlreadyExists($transaction_id) {

		$this->db->from($this->tableName)
			->where('transaction_id', $transaction_id);

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

	public function isRowByTransactionId($transaction_id) {
		$qry = $this->db->get_where($this->tableName, array('transaction_id' => $transaction_id))->row_array();
		return $qry;
	}

	public function isBetIdExists($bet_id) {
		$array=array('bet_id' => $bet_id, 'action !=' => 'credit');
		$this->db->from($this->tableName)
			->where($array);

		return $this->runExistsResult();
	}

	public function isTransIdAlreadyExists($transaction_id,$action) {
		$array=array('transaction_id' => $transaction_id, 'action' => 'credit');
		$this->db->from($this->tableName)
			->where($array);

		return $this->runExistsResult();
	}

	public function isTransIdAndBetIdAlreadyExists($transaction_id,$bet_id,$action) {
		$array=array('transaction_id' => $transaction_id, 'bet_id' => $bet_id, 'action' => 'credit');
		$this->db->from($this->tableName)
			->where($array);

		return $this->runExistsResult();
	}

	public function isCombinationIdAlreadyExists($combination_id,$action) {
		$array=array('combination_id' => $combination_id, 'action' => $action);
		$this->db->from($this->tableName)
			->where($array);

		return $this->runExistsResult();
	}

	public function isPromoIdAlreadyExists($promo_transaction_id) {
		$this->db->from($this->tableName)
			->where('promo_transaction_id', $promo_transaction_id);

		return $this->runExistsResult();
	}

	public function isBetIdOnPayoutExists($bet_id) {
		$array=array('bet_id' => $bet_id, 'action' => 'credit');
		$this->db->from($this->tableName)
			->where($array);

		return $this->runExistsResult();
	}


}