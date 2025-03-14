<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_game_logs_model.php';

class Isbseamless_wallet_transactions extends Base_game_logs_model {

	function __construct() {
		parent::__construct();
	}

	public $tableName = "isbseamless_wallet_transactions";

	public function isRowByTransactionId($transId) {
		$qry = $this->db->get_where($this->tableName, array('transactionid' => $transId))->row_array();
		return $qry;
	}

	public function isRowByRoundId($roundid) {
		$qry = $this->db->get_where($this->tableName, array('roundid' => $roundid, 'command' => 'bet'))->row_array();
		return $qry;
	}

	public function isRowByRoundIdAndWin($roundid) {
		$qry = $this->db->get_where($this->tableName, array('roundid' => $roundid, 'command' => 'win'))->row_array();
		return $qry;
	}

	public function getGameLogStatistics($dateFrom, $dateTo) {

	}

}

///END OF FILE///////