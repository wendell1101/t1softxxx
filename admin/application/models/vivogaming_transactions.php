<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_game_logs_model.php';

class Vivogaming_transactions extends Base_game_logs_model {

	public $tableName = "vivogaming_transactions";
 
	function __construct() {
		parent::__construct();
    }

	/**
	 * overview : check if transaction already exist
	 * @param  string		$transferid
	 * @return boolean
	 */
	public function getTransation($transferid, $table_name = null) {
        if (!empty($table_name)) {
            $this->tableName = $table_name;
        }

		$qry = $this->db->get_where($this->tableName, array('trans_id' => $transferid));
		return $this->getOneRow($qry);
	}    
	
	public function isTransactionExist($transaction_id, $table_name = null) {
        if (!empty($table_name)) {
            $this->tableName = $table_name;
        }

        $qry = $this->db->get_where($this->tableName, array('trans_id' => $transaction_id));
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

	public function updateResponseResultId($transactionId, $resposeResultId, $table_name = null) {
        if (!empty($table_name)) {
            $this->tableName = $table_name;
        }

		$data = ['response_result_id'=>$resposeResultId];
		return $this->updateData('id', $transactionId, $this->tableName, $data);
	}


}

///END OF FILE///////