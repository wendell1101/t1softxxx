<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_game_logs_model.php';

class Iconic_transactions extends Base_game_logs_model {

    protected $tableName = "common_seamless_wallet_transactions";    

	function __construct() {
		parent::__construct();
    }

	public function isTransactionExist($game_platform_id, $external_unique_id) {
        $qry = $this->db->get_where($this->tableName, array('game_platform_id' => $game_platform_id, 'external_unique_id' => $external_unique_id));
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

	public function updateTransaction($transactionId, $data) {
        if(empty($data) || !is_array($data)){
            return false;
        }
		
		return $this->updateData('id', $transactionId, $this->tableName, $data);
	}

    public function isTransactionExistCustom($fields = [])
    {
        $this->db->from($this->tableName)->where($fields);
        return $this->runExistsResult();
    }


}

///END OF FILE///////