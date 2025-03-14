<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_game_logs_model.php';

class Popok_gaming_seamless_transactions extends Base_game_logs_model {

	public $tableName = "popok_gaming_seamless_wallet_transactions";

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

	public function flagBetTransactionSettled($tableName, $data){
		if(!empty($tableName)){
            $this->tableName = $tableName;
        }
		
		$this->db
		->set('status', Game_logs::STATUS_SETTLED)
		->set('win_amount', $data['amount'])
		->where("transaction_id", $data['transaction_id'])
		->where("trans_type", 'bet')
		;

        if ($this->runAnyUpdate($this->tableName)) {
            return true;
        } else {
            return false;
        }
	}

    public function flagBetTransactionCancel($tableName,$data){
		if(!empty($tableName)){
            $this->tableName = $tableName;
        }
		
		$this->db
		->set('status', Game_logs::STATUS_CANCELLED)
		->where("transaction_id", $data['transaction_id'])
		->where("trans_type", 'bet')
        ;

        if ($this->runAnyUpdate($this->tableName)) {
            return true;
        } else {
            return false;
        }
	}

	public function isTransactionExist($tableName, $data) {
		if(!empty($tableName)){
            $this->tableName = $tableName;
        }
        $qry = $this->db->get_where($this->tableName, array('external_uniqueid' => $data['external_uniqueid']));
        $transaction = $this->getOneRow($qry);
		return !empty($transaction) ? $transaction : [];
	}

	public function getExistingTransactionByRoundId($tableName, $round, $balance_adjustment_method) {
        if(!empty($tableName)){
            $this->tableName = $tableName;
        }
		$qry = $this->db->get_where($this->tableName, array('round_id' => $round, 'balance_adjustment_method' => $balance_adjustment_method ));
        $transaction = $this->getOneRow($qry);
		return $transaction;
	}

    public function getBetTransactionByTransactionId($tableName, $transaction_id) {
		if(!empty($tableName)){
            $this->tableName = $tableName;
        }
		$where = [
			'transaction_id' => $transaction_id,
			'trans_type' => 'bet',
		];
        $qry = $this->db->get_where($this->tableName, $where);
        $transaction = $this->getOneRow($qry);
		return $transaction;
	}

    public function getIncompleteBets($fromdate, $todate) {
        $whereParams = [
            'trans_status' => GAME_LOGS::STATUS_UNSETTLED,
            'balance_adjustment_method' => 'debit',
        ];
        $this->db->select('round');
        $this->db->where('updated_at >=', $fromdate);
        $this->db->where('updated_at <=', $todate);
		$query = $this->db->get_where($this->tableName, $whereParams);
		return $query->result_array();
	}

	public function getPlayerFromTransactionId($mtcode){
		$whereParams = [
            'balance_adjustment_method' => 'debit',
        ];
		$this->db->select('player');
        $this->db->where('round', $mtcode);
		$query = $this->db->get_where($this->tableName, $whereParams);
		$data = $this->getOneRow($query);
		return $data ? $data->player : null;
	}

	public function updateTransactionDataWithResultCustom($fields = [], $data = [], $db = null)
    {
        $this->db->where($fields)->set($data);
        return $this->runAnyUpdateWithResult($this->tableName, $db);
    }
	
    public function insertTransaction($tableName = null, $data = []) {
        if(!empty($tableName)){
            $this->tableName = $tableName;
        }
        $inserted = $this->db->insert($this->tableName, $data);
        if($inserted === false){
            return false;
        }
        return $this->db->insert_id();
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
        $this->db->from($table)->where('round_id', $round_id)
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