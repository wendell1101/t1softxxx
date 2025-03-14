<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_game_logs_model.php';

class Bistro_transactions extends Base_game_logs_model {

	public $tableName = "bistro_transactions";
 
	function __construct() {
		parent::__construct();
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

	public function flagTransactionRefunded($externalUniqueId) {
		$data = ['status'=>Game_logs::STATUS_REFUND];
		return $this->updateData('external_uniqueid', $externalUniqueId, $this->tableName, $data);
	}
	
	public function flagBetTransactionSettled($data){
		$player_id = isset($data['player_id'])?$data['player_id']:null;
		$round_id = isset($data['round_id'])?$data['round_id']:null;
		$bet_id = isset($data['bet_id'])?$data['bet_id']:null;
		if(!$player_id || !$round_id || !$bet_id){
			return false;
		}

		$this->db->set('status', Game_logs::STATUS_SETTLED)
		->where("player_id", $player_id)
		->where("trans_type", 'bet')
		->where("round_id", $round_id)
		->where("bet_id", $bet_id);		
        if ($this->runAnyUpdate($this->tableName)) {
            return true;
        } else {
            return false;
        }
	}


    /////beyond this line has no usage

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

	public function flagTransactionVoid($externalUniqueId) {
		$data = ['status'=>Game_logs::STATUS_VOID];
		return $this->updateData('external_uniqueid', $externalUniqueId, $this->tableName, $data);
	}

    public function flagRoundVoid($player_id, $round_id, $bet_id){
		$this->db->set('status', Game_logs::STATUS_VOID)
		->where("player_id", $player_id)		
		->where("round_id", $round_id)
		->where("bet_id", $bet_id);
		if ($this->runAnyUpdate($this->tableName)) {
            return true;
        } else {
            return false;
        }
	}

}

///END OF FILE///////