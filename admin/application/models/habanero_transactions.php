<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_game_logs_model.php';

class Habanero_transactions extends Base_game_logs_model {

	public $tableName = "habanero_transactions";

	function __construct() {
		parent::__construct();
    }

	/**
	 * overview : check if transaction already exist
	 * @param  string		$transferid
	 * @return boolean
	 */
	public function getTransaction($transferid, $table_name = null) {
        if (empty($table_name)) {
            $table_name = $this->tableName;
        }

		$qry = $this->db->get_where($table_name, array('fundinfo_transferid' => $transferid));
		return $this->getOneRow($qry);
    }    

	/**
	 * overview : check if transaction already exist and retry
	 * @param  string		$transferid
	 * @return boolean
	 */
	public function isTransferIdRetry($transferid) {
        $qry = $this->db->get_where($this->tableName, array('fundinfo_transferid' => $transferid));
        $transaction = $this->getOneRow($qry);
		if ($transaction && isset($transaction->isretry) && ($transaction->isretry==true)) {
			return true;
		} else {
			return false;
		}
	}

	public function isTransactionExist($external_unique_id) {
        $qry = $this->db->get_where($this->tableName, array('external_uniqueid' => $external_unique_id));
        $transaction = $this->getOneRow($qry);
		if ($transaction) {
			return true;
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

	public function flagTrasactionDone($transferid) {
		if(empty($transferid)){
			return false;
		}
		$data = ['is_valid_transaction'=>true];
		return $this->updateData('fundinfo_transferid', $transferid, $this->tableName, $data);
	}

	public function flagTrasactionRefunded($transferid) {
		if(empty($transferid)){
			return false;
		}
		$data = ['is_refunded'=>1];
		return $this->updateData('fundinfo_transferid', $transferid, $this->tableName, $data);
	}

	public function flagGameinstanceRefunded($gameinstance_id) {
		if(empty($gameinstance_id)){
			return false;
		}
		$data = ['is_refunded'=>1];
		return $this->updateData('gameinstanceid', $gameinstance_id, $this->tableName, $data);
	}

	public function updateResponseResultId($transactionId, $resposeResultId) {
		$data = ['response_result_id'=>$resposeResultId];
		return $this->updateData('id', $transactionId, $this->tableName, $data);
	}
	
	public function updateResponseResultIdByTransferId($id, $resposeResultId, $table_name = null) {
        if (empty($table_name)) {
            $table_name = $this->tableName;
        }

		$data = ['response_result_id'=>$resposeResultId];
		return $this->updateData('fundinfo_transferid', $id, $table_name, $data);
	}

    public function isTransactionExistCustom($fields = [], $table_name = null) {
        if (empty($table_name)) {
            $table_name = $this->tableName;
        }

        $this->db->from($table_name)->where($fields);
        return $this->runExistsResult();
    }
}

///END OF FILE///////