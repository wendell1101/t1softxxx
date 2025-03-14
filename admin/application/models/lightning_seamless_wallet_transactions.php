<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_game_logs_model.php';

class Lightning_seamless_wallet_transactions extends Base_game_logs_model {

	public $tableName = "lightning_seamless_wallet_transactions";

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
		return $this->updateData('external_uniqueid', (string)$externalUniqueId, $this->tableName, $data);
	}

	
	public function isTransactionExist($transaction_id) {
        $qry = $this->db->get_where($this->tableName, array('external_uniqueid' => $transaction_id));
        $transaction = $this->getOneRow($qry);
		return !empty($transaction);
	}

	public function isTransactionsExist($whereParams) {
		if (empty($whereParams)) {
			return false;
		}
		
		$game_platform_id = $whereParams['game_platform_id'];
		$unique_ids = $whereParams['external_unique_ids'];
	
		// Prepare the unique IDs for the query
		$tempArr = array_map(function($item) {
			return "'" . $item . "'";
		}, $unique_ids);
	
		$external_uniqueids = implode(' OR external_uniqueid = ', $tempArr);
$sql = <<<EOD
SELECT id
FROM {$this->tableName}
WHERE
game_platform_id = ? AND 
external_uniqueid = $external_uniqueids
LIMIT 1
EOD;
	
		$params = [$game_platform_id];
	
		$qry = $this->db->query($sql, $params);
		return $qry->num_rows() > 0;
	}

	public function updateTransactionStatus($data){
		$status = isset($data['status']) ? $data['status'] : null;
		$game_platform_id = isset($data['game_platform_id']) ? $data['game_platform_id'] : null; 
		$unique_ids = isset($data['external_unique_ids']) ? $data['external_unique_ids'] : null;
	
		// Prepare the placeholders for the unique IDs
		$placeholders = rtrim(str_repeat('?,', count($unique_ids)), ',');

		$sql = <<<EOD
UPDATE {$this->tableName}
SET status = ?
WHERE game_platform_id = ?
AND external_uniqueid IN ($placeholders)
EOD;

		$params = array_merge([$status, $game_platform_id], $unique_ids);
		$this->db->query($sql, $params);
	
		// Return true if at least one row was updated, false otherwise
		return $this->db->affected_rows() > 0;
	}


	public function getExistingTransaction($transaction_id) {
        $qry = $this->db->get_where($this->tableName, array('external_uniqueid' => $transaction_id));
        $transaction = $this->getOneRow($qry);
		return $transaction;
	}	
	public function getRelatedBetExistingTransaction($tableName, $filterColumns=[]) {
        $qry = $this->db->get_where($tableName, $filterColumns);
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
	
	public function updateResponseResultRelated($transactionId, $data) {
		$resposeResultId = isset($data['responseResultId']) ? $data['responseResultId'] : null;
		$elapsedTime = isset($data['elapsedTime']) ? $data['elapsedTime'] : null;
		$sql = <<<EOD
UPDATE {$this->tableName}
	SET response_result_id = ?, elapsed_time =?
WHERE
transaction_id = ?
EOD;
		
		$params=[
			$resposeResultId,
			$elapsedTime,
			$transactionId,
		];

		$query = $this->runRawUpdateInsertSQL($sql, $params);
		return $query;
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
	
	public function updateRelatedData($externalUniqueId, $data) {
		return $this->updateData('external_uniqueid', $externalUniqueId, $this->tableName, $data);
	}

	public function updateBetTransactionStatus($uniqueId, $data){
		$status = isset($data['status']) ? $data['status'] : null;
		$type = isset($data['type'])  ? $data['type'] : null;
		$rgs_transaction_id = isset($data['rgs_transaction_id'])  ? $data['rgs_transaction_id'] : null;

		
		$sql = <<<EOD
UPDATE {$this->tableName}
		SET status = ?,
		rgs_related_transaction_id = ?
WHERE
external_uniqueid = ? and trans_type = ?
EOD;
	
		$params = [
			$status,
			$rgs_transaction_id,
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

	
	public function getPlayerCompleteDetailsByUsername($username, $gamePlatformId) {
        $sql = <<<EOD
SELECT

p.playerId as player_id,
p.username,
p.password,
p.active,
p.blocked,
p.frozen,
p.createdOn as created_at,
gpa.game_provider_id,
gpa.login_name game_username,
gpa.password game_password,
gpa.register game_isregister,
gpa.status game_status,
gpa.is_blocked as game_blocked,
gpa.is_demo_flag

FROM player as p
JOIN game_provider_auth as gpa ON p.playerId = gpa.player_id
WHERE gpa.login_name = ? AND gpa.game_provider_id = ?;

EOD;


        $params=[$username, $gamePlatformId];
        $qry = $this->db->query($sql, $params);
        $result = $this->getOneRow($qry);
        return  $result;
	}

}

///END OF FILE///////