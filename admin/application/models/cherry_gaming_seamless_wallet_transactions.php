<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

class Cherry_gaming_seamless_wallet_transactions extends BaseModel {

    const TRANSACTION_ROLLBACK = 'rollback';
    const TRANSACTION_CANCELLED = 'cancel';

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';

    function __construct() {
        parent::__construct();
    }

    public $tableName = "cherry_gaming_seamless_wallet_transactions";

    public function searchByExternalTransactionByRoundIdAndTransType($round_id, $trans_type) {

        $this->db->from($this->tableName)
            ->where('round_id', $round_id)
            ->where('transaction_type', $trans_type);

        $query = $this->db->get();

        $this->utils->debug_log('CG' . __METHOD__ , $this->db->last_query());

        if ($query && $query->num_rows() >= 1) {
            return $query->result_array();
        }
        else {
            return [];
        }

    }

    public function searchByExternalTransactionByRoundIdAndStatus($round_id, $status, $player_id) {

        $this->db->from($this->tableName)
            ->where('round_id', $round_id)
            ->where('status', $status)
            ->where('player_id', $player_id);

        $query = $this->db->get();

        $this->utils->debug_log('CG' . __METHOD__ , $this->db->last_query());

        if ($query && $query->num_rows() >= 1) {
            return $query->result_array();
        }
        else {
            return [];
        }

    }

    public function getBetTransactions($round_id, $status, $player_id) {

        $this->db->select("sum(bet_amount) as total_bet_amount, transaction_id")
            ->from($this->tableName)
            ->where('round_id', $round_id)
            ->where('status', $status)
            ->where('player_id', $player_id);

            $row = $this->runOneRow();
            if($row->total_bet_amount!=NULL){
                return $row;
            }else{
                return false;
            }
    }

    //Get transaction using transaction_id
    public function searchByExternalTransactionByTransactionId($transaction_id) {
        $this->db->from($this->tableName)
            ->where('transaction_id', $transaction_id);

        $query = $this->db->get();

        $this->utils->debug_log('CG' . __METHOD__ , $this->db->last_query());

        if ($query && $query->num_rows() >= 1) {
            return $query->result_array();
        }
        else {
            return [];
        }
    }

    // Get transaction details by transaction_id and status
    public function searchByExternalTransactionByTransactionIdStatus($transaction_id, $status) {
        $this->db->from($this->tableName)
            ->where('transaction_id', $transaction_id)
            ->where('status', $status);

        $query = $this->db->get();

        $this->utils->debug_log('CG' . __METHOD__ , $this->db->last_query());

        if ($query && $query->num_rows() >= 1) {
            return $query->result_array();
        }
        else {
            return [];
        }
    }

    //Update transaction 
    public function updateTransaction($params, $player_id, $round_id, $status){
        $this->db->where("player_id", $player_id);
        $this->db->where("round_id", $round_id);
        $this->db->where("status", $status);
        $this->db->update($this->tableName,$params);

        $this->utils->debug_log('CG' . __METHOD__ , $this->db->last_query());

        if ($this->db->affected_rows() == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function updateTransactionByTransactionId($params, $player_id, $round_id, $transaction_id, $status){
        $this->db->where("player_id", $player_id);
        $this->db->where("round_id", $round_id);
        $this->db->where("status", $status);
        $this->db->where("transaction_id", $transaction_id);
        $this->db->update($this->tableName,$params);

        $this->utils->debug_log('CG' . __METHOD__ , $this->db->last_query());

        if ($this->db->affected_rows() == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function refundTransaction($params){
        $inserted = $this->db->insert($this->tableName, [
            'game_platform_id' => $params['game_platform_id'],
            'player_id' => $params['player_id'],
            'transaction_type' => $params['transaction_type'],
            'transaction_id' => $params['transaction_id'],
            'game_id' => $params['game_id'],
            'round_id' => $params['round_id'],
            'before_balance' => $params['before_balance'],
            'after_balance' => $params['after_balance'],
            'start_at' => $params['start_at'],
            'end_at' => $params['end_at'],
            'status' => $params['status'],
            'amount' => $params['amount'],
            'bet_amount' => isset($params['bet_amount']) ? $params['bet_amount'] : NULL,
            'result_amount' => isset($params['result_amount']) ? $params['result_amount'] : NULL,
            'total_bet_amount' => isset($params['total_bet_amount']) ? $params['total_bet_amount'] : 0,
            'external_unique_id' => $params['external_unique_id'],
            'extra_info' => isset($params['extra_info']) ? $params['extra_info'] : NULL,
            'created_at' => $params['created_at'],
            'updated_at' => $params['updated_at'],
        ]);
        $this->utils->debug_log('ADJUST_WALLET_REFUND ' . __METHOD__ , $this->db->last_query());

        if($inserted === false){
            return false;
        }

        return $this->db->insert_id();
    }

    public function insertTransaction($params) {
        $inserted = $this->db->insert($this->tableName, [
            'game_platform_id' => $params['game_platform_id'],
            'player_id' => $params['player_id'],
            'transaction_type' => $params['transaction_type'],
            'transaction_id' => $params['transaction_id'],
            'game_id' => $params['game_id'],
            'round_id' => $params['round_id'],
            'before_balance' => $params['before_balance'],
            'after_balance' => $params['after_balance'],
            'start_at' => $params['start_at'],
            'end_at' => $params['end_at'],
            'status' => $params['status'],
            'amount' => $params['amount'],
            'bet_amount' => isset($params['bet_amount']) ? $params['bet_amount'] : 0,
            'result_amount' => isset($params['result_amount']) ? $params['result_amount'] : 0,
            'total_bet_amount' => isset($params['total_bet_amount']) ? $params['total_bet_amount'] : 0,
            'external_unique_id' => $params['external_unique_id'],
            'extra_info' => isset($params['extra_info']) ? $params['extra_info'] : NULL,
            'created_at' => $params['created_at'],
            'updated_at' => $params['updated_at'],
        ]);
        $this->utils->debug_log('TALLYNN ' . __METHOD__ , $this->db->last_query());

        if($inserted === false){
            return false;
        }

        return $this->db->insert_id();
    }

    public function updateOriginalBetToSettledStatus($transaction_id, $status){
        $updated = $this->db->where('transaction_id', $transaction_id)
        ->where('status', $status)
        ->set([
                'status' => 'SETTLED',
                'flag_of_updated_result' => 1
            ])
        ->update($this->tableName);

        $this->utils->debug_log('CG' . __METHOD__ , $this->db->last_query());

        return true;
    }

}