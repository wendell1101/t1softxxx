<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_game_logs_model.php';

class Smartsoft_seamless_transactions extends Base_game_logs_model {

	public $tableName = "smartsoft_seamless_wallet_transactions";

	function __construct() {
		parent::__construct();
    }

	public function setTableName($table=null){
		$this->tableName = $table;
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

	public function flagBetTransactionSettled($tableName, $data){
		$player_id 			= isset($data['player_id'])?$data['player_id']:null;
		$betTransactionId   = isset($data['bet_transaction_id']) ? $data['bet_transaction_id'] : null;
		if(!$player_id || !$betTransactionId){
			return false;
		}
		$this->db
		->set('trans_status', Game_logs::STATUS_SETTLED)
		->where("player_id", $player_id)
		->where("transaction_id", $betTransactionId);

        if ($this->runAnyUpdate($tableName)) {
            return true;
        } else {
            return false;
        }
	}

	public function flagBetTransactionAllRoundSettled($tableName, $data){
		$player_id 			= isset($data['player_id'])? $data['player_id']:null;
		$round_id   		= isset($data['round_id']) ? $data['round_id'] : null;
		if(!$player_id || !$round_id){
			return false;
		}
		$this->db
		->set('trans_status', Game_logs::STATUS_SETTLED)
		->where("trans_status !=", Game_logs::STATUS_CANCELLED)
		->where("player_id", $player_id)
		->where("round_id", $round_id);

        if ($this->runAnyUpdate($tableName)) {
            return true;
        } else {
            return false;
        }
	}

	public function flagBetTransactionUnsettled($tableName, $data){
		$player_id 			= isset($data['player_id'])?$data['player_id']:null;
		$transactionId   	= isset($data['transaction_id']) ? $data['transaction_id'] : null;
		if(!$player_id || !$transactionId){
			return false;
		}
		$this->db
		->set('trans_status', Game_logs::STATUS_CANCELLED)
		->where("player_id", $player_id)
		->where("transaction_id", $transactionId);

        if ($this->runAnyUpdate($tableName)) {
            return true;
        } else {
            return false;
        }
	}


	public function isTransactionExist($tableName = null, $transaction_id, $balance_adjustment_method) {
        $qry = $this->db->get_where($tableName, array('wager_id' => $transaction_id, 'balance_adjustment_method' => $balance_adjustment_method ));
        $transaction = $this->getOneRow($qry);
		return !empty($transaction);
	}

	public function getExistingTransaction($transaction_id, $balance_adjustment_method) {
        $qry = $this->db->get_where($this->tableName, array('wager_id' => $transaction_id, 'balance_adjustment_method' => $balance_adjustment_method ));
        $transaction = $this->getOneRow($qry);
		return $transaction;
	}

	public function updateTransactionDataWithResultCustom($fields = [], $data = [], $db = null)
    {
        $this->db->where($fields)->set($data);
        return $this->runAnyUpdateWithResult($this->tableName, $db);
    }
	
    public function insertTransactionData($tableName = null, $data = [], $db = null)
    {
        return $this->insertData($tableName, $data, $db);
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

	public function getRoundData($table, $whereParams) {
		
		if(empty($whereParams)){
			return false;
		}
		
		$query = $this->db->get_where($table, $whereParams);

		return $query->result_array();
	
	}
	public function getTransactionDetails($table, $transaction_id){
        $this->db->from($table)->where('transaction_id', $transaction_id)
            ->order_by('created_at', 'asc');

        $query = $this->db->get();

        if ($query && $query->num_rows() >= 1) {
            return $query->row();
        }
        else {
            return [];
        }
    }

	public function getRoundDetails($table, $round_id){
        $this->db->from($table)
			->where('round_id', $round_id)
            ->order_by('created_at', 'asc');

        $query = $this->db->get();

        if ($query && $query->num_rows() >= 1) {
            return $query->row();
        }
        else {
            return [];
        }
    }
}

///END OF FILE///////