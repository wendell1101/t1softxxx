<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_game_logs_model.php';

class Amb_transactions extends Base_game_logs_model {

	public $tableName = "amb_transactions";
 
	function __construct() {
		parent::__construct();
    }

	/**
	 * overview : check if transaction already exist
	 * @param  string		$transferid
	 * @return boolean
	 */
	public function getTransation($transferid) {
		$qry = $this->db->get_where($this->tableName, array('trans_id' => $transferid));
		return $this->getOneRow($qry);
	}    
	
	public function isTransactionExist($external_unique_id) {
        $qry = $this->db->get_where($this->tableName, array('trans_id' => $external_unique_id));
        $transaction = $this->getOneRow($qry);
		if ($transaction) {
			return true;
		} else {
			return false;
		}
	}  
	
	public function getTransactionByParams($whereParams) {
		if(empty($whereParams)){
			return false;
		}
        $qry = $this->db->get_where($this->tableName, $whereParams);
        $transaction = $this->getOneRow($qry);
		if ($transaction) {
			return $transaction;
		} else {
			return false;
		}
	}
	
	public function getTransactionByParamsArray($whereParams) {
		if(empty($whereParams)){
			return false;
		}
        $qry = $this->db->get_where($this->tableName, $whereParams);
        $transaction = $this->getOneRowArray($qry);
		if ($transaction) {
			return $transaction;
		} else {
			return false;
		}
	}

	public function getGameLogStatistics($dateFrom, $dateTo) {
		return null;
	}

	public function createTransaction($params) {
		return null;
	}

	public function updateResponseResultId($transactionId, $resposeResultId) {
		$data = ['response_result_id'=>$resposeResultId];
		return $this->updateData('id', $transactionId, $this->tableName, $data);
	}

	public function flagBetCancelled($externalUniqueId) {
		$data = ['status'=>Game_logs::STATUS_CANCELLED];
		return $this->updateData('external_uniqueid', $externalUniqueId, $this->tableName, $data);
	}

	public function flagTransactionVoid($externalUniqueId) {
		$data = ['status'=>Game_logs::STATUS_VOID];
		return $this->updateData('external_uniqueid', $externalUniqueId, $this->tableName, $data);
	}

    public function flagRoundVoid($player_id, $round_id){
		$data = ['status'=>Game_logs::STATUS_VOID];
        $this->db->where("player_id", $player_id);
        $this->db->where("round_id", $round_id);
        $this->db->update($this->tableName,$data);
        if ($this->db->affected_rows()) {
            return true;
        } else {
            return false;
        }
	}
	
	public function flagBetSettled($data){
		$player_id = isset($data['player_id'])?$data['player_id']:null;
		$round_id = isset($data['round_id'])?$data['round_id']:null;
		if(!$player_id || !$round_id){
			return false;
		}
		$data = ['status'=>Game_logs::STATUS_SETTLED];
        $this->db->where("player_id", $player_id);
        $this->db->where("trans_type", 'bet');
        $this->db->where("round_id", $round_id);
        $this->db->update($this->tableName,$data);
        if ($this->db->affected_rows()) {
            return true;
        } else {
            return false;
        }
	}

}

///END OF FILE///////