<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_game_logs_model.php';

class Jili_seamless_transactions extends Base_game_logs_model {

	public $tableName = "jili_seamless_wallet_transactions";
 
	function __construct() {
		parent::__construct();
    }
	
	public function getTransactionByParamsArray($whereParams) {
		if(empty($whereParams)){
			return false;
		}
		$whereParams['is_failed'] = 0;
        $qry = $this->db->get_where($this->tableName, $whereParams);
        $transaction = $this->getOneRowArray($qry);
		if ($transaction) {
			return $transaction;
		} else {
			return false;
		}
	}

	public function flagTransactionCancelled($externalUniqueId) {
		$data = ['trans_status'=>Game_logs::STATUS_REFUND];
		return $this->updateData('external_uniqueid', $externalUniqueId, $this->tableName, $data);
	}


	
	public function flagBetTransactionSettled($data){
		$player_id = isset($data['player_id'])?$data['player_id']:null;
		$round_id = isset($data['round'])?$data['round']:null;
		
		if(!$player_id || !$round_id){
			return false;
		}

		$this->db->set('trans_status', Game_logs::STATUS_SETTLED)
		->where("player_id", $player_id)		
		->where("round", $round_id);		
        if ($this->runAnyUpdate($this->tableName)) {
            return true;
        } else {
            return false;
        }
	}


	
	public function flagSessionBetTransactionSettled($data){
		$player_id = isset($data['player_id'])?$data['player_id']:null;
		$session_id = isset($data['session_id'])?$data['session_id']:null;
		
		if(!$player_id || !$session_id){
			return false;
		}

		$this->db->set('trans_status', Game_logs::STATUS_SETTLED)
		->where("player_id", $player_id)		
		->where("session_id", $session_id)		
		->where("type", 1)		
		->where("trans_status", 2)	
		->where("is_failed", 0);		
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

	public function updateResponseResultId($tableName,$transactionId, $resposeResultId) {
		$data = ['response_result_id'=>$resposeResultId];
		return $this->updateData('external_uniqueid', $transactionId, $tableName, $data);
	}

	public function flagTransactionVoid($externalUniqueId) {
		$data = ['trans_status'=>Game_logs::STATUS_VOID];
		return $this->updateData('external_uniqueid', $externalUniqueId, $this->tableName, $data);
	}

    public function flagRoundVoid($player_id, $round_id, $bet_id){
		$this->db->set('trans_status', Game_logs::STATUS_VOID)
		->where("player_id", $player_id)		
		->where("round_id", $round_id)
		->where("bet_id", $bet_id);
		if ($this->runAnyUpdate($this->tableName)) {
            return true;
        } else {
            return false;
        }
	}


	public function updateDataByTransactionID($data, $external_unique_id, $custom_table = null){
        if(!empty($custom_table)){
            $this->tableName = $custom_table;
        }
        $this->db->where('external_uniqueid', (string)$external_unique_id)
			->where('is_failed', 0)
            ->set($data);

        $this->runAnyUpdate($this->tableName);
    }

}

///END OF FILE///////