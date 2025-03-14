<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_game_logs_model.php';

class Pinnacle_seamless_transactions extends Base_game_logs_model {

	public $tableName = "pinnacle_seamless_wallet_transactions";
 
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

	public function getRoundData($table, $whereParams, $limit = null) {
		if (empty($whereParams)) {
			return false;
		}

		if ($limit !== null) {
			$this->db->limit($limit);
		}
	
		$query = $this->db->get_where($table, $whereParams);
	
		// Return one row if limit is 1, otherwise all rows
		if ($limit === 1) {
			return $query->row_array(); 
		}
	
		return $query->result_array();
	}

	public function getSingleRoundData($table, $whereParams) {
		$playerId = isset($whereParams['player_id']) ? $whereParams['player_id'] : null;
		$wagerId = isset($whereParams['wager_id']) ? $whereParams['wager_id'] : null;

        $sql = <<<EOD
SELECT 
wager_master_id,
bet_type
FROM {$table}
WHERE `wager_id` = ?
AND `player_id` = ?
LIMIT 1;

EOD;


        $params=[$wagerId, $playerId];
        $qry = $this->db->query($sql, $params);
        $result = $this->getOneRow($qry);
        return  $result;
	}
	public function getRoundDataMerged($table, $whereParams) {
		
		if(empty($whereParams)){
			return false;
		}

		$this->db->select("wager_id, round_id, SUM(IF(wallet_adjustment_mode='debit',amount,0)) debit_amount,
    SUM(IF(wallet_adjustment_mode='credit',amount,0)) credit_amount", false);
		$this->db->where($whereParams);
		$query = $this->db->get($table);

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

	public function setTransactionStatus($uniqueid, $status, $settledAt, $table = null) {
		$data = ['status'=>$status, 'settled_at'=>$settledAt];
		if(!empty($table)){
			return $this->updateData('external_uniqueid', $uniqueid, $table, $data);
		}else{
			return $this->updateData('external_uniqueid', $uniqueid, $this->tableName, $data);
		}
		
	}

	public function getGameLogStatistics($dateFrom, $dateTo) {
		return null;
	}

}

///END OF FILE///////