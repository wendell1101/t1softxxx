<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_game_logs_model.php';

class Dragoonsoft_seamless_wallet_transactions extends Base_game_logs_model {

	public $tableName = "dragoonsoft_seamless_wallet_transactions";

	function __construct() {
		parent::__construct();
    }

	public function setTableName($table){
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

	
	public function isTransactionExist($transaction_id) {
        $qry = $this->db->get_where($this->tableName, array('external_uniqueid' => $transaction_id));
        $transaction = $this->getOneRow($qry);
		return !empty($transaction);
	}

	public function getExistingTransaction($transaction_id) {
        $qry = $this->db->get_where($this->tableName, array('external_uniqueid' => $transaction_id));
        $transaction = $this->getOneRow($qry);
		return $transaction;
	}	
	public function getRelatedBetExistingTransaction($uniqueId) {
        $qry = $this->db->get_where($this->tableName, array('external_uniqueid' => $uniqueId));
        $transaction = $this->getOneRow($qry);
		return $transaction;
	}	

	public function checkIfIsAlreadyRefunded($reference_id) {
        $qry = $this->db->get_where($this->tableName, array('external_uniqueid' => $reference_id));
        $transaction = $this->getOneRow($qry);
		$success = false;
		if($transaction){
			 if($transaction->status == Game_logs::STATUS_REFUND){
				$succes = true;
			 }
		}
		return false;
	}	

	public function getUniqueExistingTransaction($transaction_id) {
        $qry = $this->db->get_where($this->tableName, array('external_uniqueid' => $transaction_id));
        $transaction = $this->getOneRow($qry);
		return $transaction;
	}


	
	public function getTransaction($transaction_id) {
		$qry = $this->db->get_where($this->tableName, array('external_uniqueid' => $transaction_id));
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

	public function updateAfterBalanceByTransactionId($transactionId,$afterBalance){
		
        $sql = <<<EOD
UPDATE {$this->tableName}
    SET after_balance = ?
WHERE
external_uniqueid = ?
EOD;

        $params=[
			$afterBalance,
            $transactionId,
        ];

        $query = $this->runRawUpdateInsertSQL($sql, $params);

        return $query;
    }

	public function updateBetTransactionStatus($uniqueId, $data){
		$status = isset($data['status']) ? $data['status'] : null;
		$type = isset($data['type'])  ? $data['type'] : null;

		
		$sql = <<<EOD
UPDATE {$this->tableName}
		SET status = ?
WHERE
external_uniqueid = ? and trans_type = ?
EOD;
	
		$params = [
			$status,
			$uniqueId,
			$type,
		];

	
		$query = $this->runRawUpdateInsertSQL($sql, $params);
		return $query;
	}
	
	public function setTransactionStatus($gamePlatformId, $uniqueIdValue, $uniqueId='external_unique_id', $status="ok"){

        if(empty($gamePlatformId) || empty($uniqueIdValue)){
            return false;
        }

        $sql = <<<EOD
UPDATE {$this->tableName}
    SET trans_status = ?
WHERE
game_platform_id = ? and {$uniqueId} = ?
EOD;

        $params=[
            $status,
            $gamePlatformId,
            $uniqueIdValue,
        ];

        $query = $this->runRawUpdateInsertSQL($sql, $params);

        return $query;
    }


}

///END OF FILE///////