<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

class Ebet_seamless_wallet_transactions extends BaseModel {

    const TRANSACTION_ROLLBACK = 'rollback';
    const TRANSACTION_CANCELLED = 'cancel';

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';

    function __construct() {
        parent::__construct();
    }

    public $tableName = "ebet_seamless_wallet_transactions";

    public function searchByExternalTransactionIdBySeqNo($seqNo) {

        $this->db->from($this->tableName)
            ->where('seqNo', $seqNo);

        $query = $this->db->get();

        $this->utils->debug_log('EBET' . __METHOD__ , $this->db->last_query());

        if ($query && $query->num_rows() >= 1) {
            return $query->result_array();
        }
        else {
            return [];
        }

    }

    public function searchByExternalTransactionByRoundIdAndTransType($round_id, $trans_type) {

        $this->db->from($this->tableName)
            ->where('round_id', $round_id)
            ->where('transaction_type', $trans_type);

        $query = $this->db->get();

        $this->utils->debug_log('EBET' . __METHOD__ , $this->db->last_query());

        if ($query && $query->num_rows() >= 1) {
            return $query->result_array();
        }
        else {
            return [];
        }

    }

    public function searchByExternalTransactionByRoundIdAndStatus($round_id, $status) {

        $this->db->from($this->tableName)
            ->where('round_id', $round_id)
            ->where('status', $status);

        $query = $this->db->get();

        $this->utils->debug_log('EBET' . __METHOD__ , $this->db->last_query());

        if ($query && $query->num_rows() >= 1) {
            return $query->result_array();
        }
        else {
            return [];
        }

    }

    public function searchByExternalTransactionBySeqNo($round_id, $seq_no) {

        $this->db->from($this->tableName)
            ->where('round_id', $round_id)
            ->where('seqNo', $seq_no);

        $query = $this->db->get();

        $this->utils->debug_log('EBET' . __METHOD__ , $this->db->last_query());

        if ($query && $query->num_rows() >= 1) {
            return $query->result_array();
        }
        else {
            return [];
        }

    }

    //Get transaction using transaction_id / bet_id
    public function searchByExternalTransactionByTransactionId($transaction_id) {

        $this->db->from($this->tableName)
            ->where('transaction_id', $transaction_id);

        $query = $this->db->get();

        $this->utils->debug_log('EBET' . __METHOD__ , $this->db->last_query());

        if ($query && $query->num_rows() >= 1) {
            return $query->result_array();
        }
        else {
            return [];
        }

    }

    public function searchByExternalTransactionIdBySeqNoAndStatus($seqNo, $status) {

        $this->db->from($this->tableName)
            ->where('seqNo', $seqNo)
            ->where('status', $status)
            ->order_by('created_at', 'asc');

        $query = $this->db->get();

        $this->utils->debug_log('EBET_SEAMLESS ' . __METHOD__ , $this->db->last_query());

        if ($query && $query->num_rows() >= 1) {
            return $query->result_array();
        }
        else {
            return [];
        }

    }

    public function searchByTransactionByExternalUniqueId($external_unique_id) {

        $this->db->from($this->tableName)
            ->where('external_unique_id', $external_unique_id)
            ->order_by('created_at', 'asc');

        $query = $this->db->get();

        $this->utils->debug_log('EBET ' . __METHOD__ , $this->db->last_query());

        if ($query && $query->num_rows() >= 1) {
            return $query->result_array();
        }
        else {
            return [];
        }

    }

    public function searchTransactionByTransactionIdAndStatus($transaction_id, $status) {

        $this->db->from($this->tableName)
            ->where('transaction_id', $transaction_id)
            ->where('status', $status)
            ->order_by('created_at', 'asc');

        $query = $this->db->get();

        $this->utils->debug_log('EBET ' . __METHOD__ , $this->db->last_query());

        if ($query && $query->num_rows() >= 1) {
            return $query->result_array();
        }
        else {
            return [];
        }

    }

    public function searchTransactionBySeqNoAndRoundId($seq_nos, $round_id){
        $seq_no_arr = explode (",", $seq_nos);

        $this->db->from($this->tableName)
            ->where_in('seqNo', $seq_no_arr)
            ->where('round_id', $round_id)
            ->order_by('created_at', 'asc');

        $query = $this->db->get();

        $this->utils->debug_log('EBET ' . __METHOD__ , $this->db->last_query());

        if ($query && $query->num_rows() >= 1) {
            return $query->result_array();
        }
        else {
            return [];
        }
    }

    public function searchTransactionBySeqNoAndType($seq_no, $transaction_type){

        $this->db->from($this->tableName)
            ->where('seqNo', $seq_no)
            ->where('transaction_type', $transaction_type)
            ->order_by('created_at', 'asc');

        $query = $this->db->get();

        $this->utils->debug_log('ADJUST_WALLET_REFUND' . __METHOD__ , $this->db->last_query());

        if ($query && $query->num_rows() >= 1) {
            return $query->result_array();
        }
        else {
            return [];
        }
    }

    public function refundTransaction($params){
        $inserted = $this->db->insert($this->tableName, [
            'game_platform_id' => $params['game_platform_id'],
            'token' => $params['token'],
            'player_id' => $params['player_id'],
            'currency' => $params['currency'],
            'transaction_type' => $params['transaction_type'],
            'transaction_id' => $params['transaction_id'],

            'amount' => $params['amount'],
            'win_amount' => $params['amount'],
            'result_amount' => $params['amount'],
            'round_id' => $params['round_id'],
            'refund_money' => $params['refund_money'],
            'before_balance' => $params['before_balance'],
            'after_balance' => $params['after_balance'],
            'start_at' => $params['start_at'],
            'end_at' => $params['end_at'],
            'status' => $params['status'],
            'extra_info' => $params['extra_info'],

            'external_unique_id' => $params['external_unique_id'],
            'seqNo' => $params['seqNo'],

            'created_at' => $params['created_at'],
            'updated_at' => $params['updated_at'],

            'response_status' => $params['response_status'],
            'player_username' => $params['player_username']
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
            'token' => $params['token'],
            'player_id' => $params['player_id'],
            'currency' => $params['currency'],
            'transaction_type' => $params['transaction_type'],
            'transaction_id' => $params['transaction_id'],
            'game_id' => $params['game_id'],
            'round_id' => $params['round_id'],
            'amount' => $params['amount'],
            'before_balance' => $params['before_balance'],
            'after_balance' => $params['after_balance'],
            'start_at' => $params['start_at'],
            'end_at' => $params['end_at'],
            'status' => $params['status'],
            'bet_amount' => $params['bet_amount'],
            'win_amount' => isset($params['win_amount']) ? $params['win_amount'] : NULL,
            'result_amount' => isset($params['result_amount']) ? $params['result_amount'] : NULL,
            'external_unique_id' => $params['external_unique_id'],
            'extra_info' => isset($params['extra_info']) ? $params['extra_info'] : NULL,
            'seqNo' => $params['seqNo'],
            'betType' => $params['betType'],
            'odds' => isset($params['odds']) ? $params['odds'] : NULL,
            'validBet' => isset($params['validBet']) ? $params['validBet'] : NULL,
            'created_at' => $params['created_at'],
            'updated_at' => $params['updated_at'],
            'response_status' => $params['response_status'],
            'player_username' => $params['player_username']
        ]);
        $this->utils->debug_log('TALLYNN ' . __METHOD__ , $this->db->last_query());

        if($inserted === false){
            return false;
        }

        return $this->db->insert_id();
    }

    public function updateTransaction($params, $status){
        $updated = $this->db->where('transaction_id', $params['transaction_id'])
        ->where('seqNo', $params['seqNo'])
        ->where('status', $status)
        ->set([
                'before_balance' => $params['before_balance'],
                'after_balance' => $params['after_balance'],
                'amount' => $params['amount'],
                'status' => $params['status'],
                'bet_amount' => $params['bet_amount'],
                'result_amount' => isset($params['result_amount']) ? $params['result_amount'] : "",
                'flag_of_updated_result' => 1,
                'response_status' => $params['response_status'],
            ])
        ->update($this->tableName);

        $this->utils->debug_log('TALLYNN' . __METHOD__ , $this->db->last_query());

        return true;
    }

    public function updateOriginalBetToSettledStatus($transaction_id, $status){
        $updated = $this->db->where('transaction_id', $transaction_id)
        ->where('status', $status)
        ->set([
                'status' => 'SETTLED',
                'flag_of_updated_result' => 1
            ])
        ->update($this->tableName);

        $this->utils->debug_log('EBET_SEAMLESS' . __METHOD__ , $this->db->last_query());

        return true;
    }

    public function updateOriginalBetToRefundStatus($transaction_id, $status){
        $updated = $this->db->where('transaction_id', $transaction_id)
        ->where('status', $status)
        ->set([
                'status' => 'REFUNDED',
                'flag_of_updated_result' => 1
            ])
        ->update($this->tableName);

        $this->utils->debug_log('EBET_SEAMLESS' . __METHOD__ , $this->db->last_query());

        return true;
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