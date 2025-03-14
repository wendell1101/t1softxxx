<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_game_logs_model.php';

class Pgsoft_seamless_game_logs extends Base_game_logs_model {

	function __construct() {
		parent::__construct();
	}

	public $tableName = "pgsoft_seamless_game_logs";

	public function isRowByTransactionId($transId) {
		$qry = $this->db->get_where($this->tableName, array('TransGuid' => $transId))->row_array();
		return $qry;
	}

	public function isRowByRoundId($roundId) {
		$qry = $this->db->get_where($this->tableName, array('GameId' => $roundId))->row_array();
		return $qry;
	}

	public function isRowByTransAndRoundId($transId, $gameId) {
		$qry = $this->db->get_where($this->tableName, array('TransGuid' => $transId, 'GameId' => $gameId))->row_array();
		return $qry;
	}

	public function getGameLogStatistics($dateFrom, $dateTo) {

	}

}

///END OF FILE///////