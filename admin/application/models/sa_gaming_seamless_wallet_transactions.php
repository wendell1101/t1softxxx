<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

class Sa_gaming_seamless_wallet_transactions extends BaseModel {

    const TRANSACTION_BET = 'bet';
    const TRANSACTION_WIN = 'win';
    const TRANSACTION_LOSE = 'lose';
    const TRANSACTION_CANCEL = 'cancel';

    const STATUS_OK = 'ok';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_WAITING = 'waiting';

    function __construct() {
        parent::__construct();
    }

    public $tableName = "sa_gaming_seamless_wallet_transactions";
    public $gamePlatformId = SA_GAMING_SEAMLESS_API;

    public function generateHash($data){
		$hash = implode('', $data);
		return md5($hash);
	}

     /**
     * Check transaction if exist based in unique id
     *
     * @param int $gamePlatformId
     *
     * @return boolean
    */
    public function isTransactionExist($externalUniqueId)
    {
        $this->db->from($this->tableName)
        ->where('external_unique_id',$externalUniqueId);

        $query = $this->db->get();

        if ($query && $query->num_rows() >= 1) {
            return $query->result_array();
        }
        else {
            return [];
        }
    }

    /**
     * update Transaction to refunded
     *
     * @param int $transactionId
     * @param int $uniqueId
     *
     * @return int
    */
    public function setTransactionStatus($uniqueIdValue, $uniqueId='external_unique_id', $status=self::STATUS_OK){
        if(empty($uniqueIdValue)){
            return false;
        }

        $this->db->set('status', $status);
        $this->db->where($uniqueId, $uniqueIdValue);
        $this->db->update($this->tableName);

        return true;
    }

      /**
     * insert Transaction
     *
     * @param array $raw_data
     *
     * @return int
    */
    public function insertTransactionRecord($raw_data){
        $reference_transaction_id = isset($raw_data['reference_transaction_id']) ? $raw_data['reference_transaction_id'] : null;

        $generatedHash = $this->generateHash([
            $raw_data['before_balance'],
            $raw_data['after_balance'],
            $raw_data['hostid'],
            $raw_data['amount'],
            $reference_transaction_id,
            $this->gamePlatformId
        ]);

        if(isset($this->request['hostid'])){
            $gameid = $this->request['hostid'];
        }else if(isset($this->request['game_id'])){
            $gameid = $this->request['game_id'];
        }else{
            $gameid = null;
        }
        $inserted = $this->db->insert($this->tableName, [
            'game_platform_id' => $this->gamePlatformId,
            'transaction_id' => isset($raw_data['txnid'])?$raw_data['txnid']:null,
            'amount' => isset($raw_data['amount'])?floatVal($raw_data['amount']):0,
            'before_balance' => isset($raw_data['before_balance'])?floatVal($raw_data['before_balance']):0,
            'after_balance' => isset($raw_data['after_balance'])?floatVal($raw_data['after_balance']):0,
            'player_id' => isset($raw_data['player_id'])?$raw_data['player_id']:null,
            'round_id' => isset($raw_data['gameid'])?$raw_data['gameid']:null,
            'game_id' => $gameid,
            'transaction_type' => isset($raw_data['callType'])?$raw_data['callType']:null,
            'status' => isset($raw_data['status']) ? $raw_data['status'] : self::STATUS_OK,
            'response_result_id' => isset($raw_data['response_result_id'])?$raw_data['response_result_id']:null,
            'extra_info' => @json_encode($raw_data['extra_info']),
            'start_at' => isset($raw_data['start_at'])?$raw_data['start_at']:null,
            'end_at' => isset($raw_data['end_at'])?$raw_data['end_at']:null,
            'external_unique_id' => $raw_data['txnid'],
            'reference_transaction_id' => $reference_transaction_id,
            'elapsed_time' => intval($this->utils->getExecutionTimeToNow()*1000),
            'md5_sum' => $generatedHash 
        ]);
        $this->utils->debug_log('SA_GAMING_SEAMLESS ' . __METHOD__ , $this->db->last_query());

        if($inserted === false){
            return false;
        }

        return $this->db->insert_id();
	}

    /**
     * Check transaction if exist based in unique id
     *
     * @param int $gameId
     * @param int $txnReverseId
     *
     * @return boolean
    */
    public function getCancelRecordWithTxnReverseId($gameId, $txnReverseId){
		$this->db->from($this->tableName)
		->where('transaction_id !=', $txnReverseId)
		->where('transaction_type', 'cancel')
		->where('status', self::STATUS_WAITING)
        ->where("game_id", $gameId);
		$query = $this->db->get();
		
		$data = $query->result_array();
		$this->utils->error_log("SA_GAMING SEAMLESS SERVICE: (getCancelRecordWithTxnReverseId) last db query: ", $this->db->last_query());
		$this->utils->error_log("SA_GAMING SEAMLESS SERVICE: (getCancelRecordWithTxnReverseId) data: ", $data);
						

		foreach($data as $row){
			$extraInfoDecoded = json_decode($row['extra_info'], true);
			$this->utils->error_log("SA_GAMING SEAMLESS SERVICE: (getCancelRecordWithTxnReverseId) extraInfoDecoded: ", $extraInfoDecoded);
			if(isset($extraInfoDecoded['txn_reverse_id']) && $extraInfoDecoded['txn_reverse_id']==$txnReverseId){
				//found cancel
				return $row;
			}
		}

		return false;
	}

    public function updateResponseResultId($wallet_transaction_id, $response_result_id) {
        $this->db->where('id', $wallet_transaction_id)
            ->set([
                'response_result_id' => $response_result_id,
                'request_id' => $this->utils->getRequestId()
            ]);

        $this->runAnyUpdate($this->tableName);
    }

    public function isPlayerTransactionAlreadyProcessedByTypeUserGameRound($transaction_type, $user_id, $game_id, $round_id)
    {
        $this->CI->db->from($this->tableName)
        ->where('transaction_type', $transaction_type)
        ->where('user_id', $user_id)
        ->where('game_id', $game_id)
        ->where('round_id', $round_id);

        return $this->runExistsResult();
    }

    public function queryPlayerTransactionsByRound($user_id, $game_id, $round_id)
    {
        $this->db->from($this->tableName)
        ->where('user_id', $user_id)
        ->where('game_id', $game_id)
        ->where('round_id', $round_id)
        ->order_by('created_at', 'asc');

        return $this->runMultipleRowArray();
    }
}