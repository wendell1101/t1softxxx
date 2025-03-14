<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_game_logs_model.php';

class Ygg_transactions extends Base_game_logs_model {

	public $tableName = "ygg_seamless_wallet_transactions";
 
	function __construct() {
		parent::__construct();
    }

	public function setTableName($table){
		$this->tableName = $table;
	}

	public function getTransactionByParamsArray($whereParams, $table = null) {
		
		if(empty($whereParams)){
			return false;
		}
		if(!empty($table)){
			$qry = $this->db->get_where($table, $whereParams);
		}else{
			$qry = $this->db->get_where($this->tableName, $whereParams);
		}
        
        $transaction = $this->getOneRowArray($qry);
		if ($transaction) {
			return $transaction;
		} else {
			return false;
		}
	}

	public function getRoundData($table, $whereParams) {
		
		if(empty($whereParams)){
			return false;
		}
		
		$query = $this->db->get_where($table, $whereParams);

		return $query->result_array();
	
	}

	public function getTransactionObjectsByField($game_platform_id, $data = [], $field = 'external_unique_id', $transaction_type = null) {
		$this->db->from($this->tableName)
			->where("game_platform_id", $game_platform_id)
			->where_in($field, $data);

		if($transaction_type != null) {
			$this->db->where('transaction_type', $transaction_type);
		}
		$query = $this->db->get();

		return $this->getMultipleRow($query);
	}
	
	
	public function isTransactionExistInOtherTable($table, $uniqueId, $type) {
        $qry = $this->db->get_where($table, array('external_uniqueid' => strval($uniqueId), 'transaction_type' => strval($type)));
        $transaction = $this->getOneRow($qry);
		if ($transaction) {
			return true;
		} else {
			return false;
		}
	}  

	public function flagRoundCancelled($round, $table = null) {
		$data = ['status'=>Game_logs::STATUS_CANCELLED];
		if(!empty($table)){
			return $this->updateData('round_id', $round, $table, $data);
		}else{
			return $this->updateData('round_id', $round, $this->tableName, $data);
		}
		
	}

	public function flagRoundSettled($round, $table = null) {
		$data = ['status'=>Game_logs::STATUS_SETTLED];
		if(!empty($table)){
			return $this->updateData('round_id', $round, $table, $data);
		}else{
			return $this->updateData('round_id', $round, $this->tableName, $data);
		}
		
	}

	public function getGameLogStatistics($dateFrom, $dateTo) {
		return null;
	}

}

///END OF FILE///////