<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

class Evenbet_poker_seamless_wallet_transactions extends BaseModel {

    const TRANSACTION_ROLLBACK = 'rollback';
    const TRANSACTION_CANCELLED = 'cancel';

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';

    function __construct() {
        parent::__construct();
    }

    public $tableName = "evenbet_poker_seamless_wallet_transactions";

    public function searchByExternalTransactionByTransactionId($transactionId) {

        $this->db->from($this->tableName)
            ->where('transaction_id', $transactionId);

        $query = $this->db->get();

        $this->utils->debug_log('Evenbet' . __METHOD__ , $this->db->last_query());

        if ($query && $query->num_rows() >= 1) {
            return $query->result_array();
        }
        else {
            return [];
        }

    }

    public function searchByExternalTransactionByTransactionIdAndStatus($transactionId, $status) {

        $this->db->from($this->tableName)
            ->where('transaction_id', $transactionId)
            ->where('status', $status);

        $query = $this->db->get();

        $this->utils->debug_log('Evenbet' . __METHOD__ , $this->db->last_query());

        if ($query && $query->num_rows() >= 1) {
            return $query->result_array();
        }
        else {
            return [];
        }

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
            'extra_info' => isset($params['extra_info']) ? $params['extra_info'] : NULL,
            'bet_amount' => $params['bet_amount'],
            'win_amount' => isset($params['win_amount']) ? $params['win_amount'] : NULL,
            'result_amount' => isset($params['result_amount']) ? $params['result_amount'] : NULL,
            'external_unique_id' => $params['external_unique_id'],
            'created_at' => $params['created_at'],
            'updated_at' => $params['updated_at'],
            'tournament_id' => $params['tournament_id'],
            'transaction_sub_type' => $params['transaction_sub_type'],
            'tournament_buy_in' => $params['tournament_buy_in'],
            'tournament_entryFee' => $params['tournament_entryFee'],
            'tournament_bounty_knockout' => $params['tournament_bounty_knockout']
        ]);

        $this->utils->debug_log('Evenbet' . __METHOD__ , $this->db->last_query());

        if($inserted === false){
            return false;
        }

        return $this->db->insert_id();
    }


    public function refundTransaction($params){
        $inserted = $this->db->insert($this->tableName, [
            'game_platform_id' => $params['game_platform_id'],
            'token' => isset($params['token']) ? $params['token'] : NULL,
            'player_id' => $params['player_id'],
            'currency' => $params['currency'],
            'transaction_type' => $params['transaction_type'],
            'transaction_id' => $params['transaction_id'],
            'game_id' => isset($params['game_id']) ? $params['game_id'] : NULL,
            'round_id' => $params['round_id'],
            'amount' => $params['amount'],
            'before_balance' => $params['before_balance'],
            'after_balance' => $params['after_balance'],
            'start_at' => $params['start_at'],
            'end_at' => $params['end_at'],
            'status' => $params['status'],
            'extra_info' => isset($params['extra_info']) ? $params['extra_info'] : NULL,
            'bet_amount' => isset($params['bet_amount']) ? $params['bet_amount'] : NULL,
            'win_amount' => isset($params['win_amount']) ? $params['win_amount'] : NULL,
            'result_amount' => isset($params['result_amount']) ? $params['result_amount'] : NULL,
            'external_unique_id' => $params['external_unique_id'],
            'created_at' => $params['created_at'],
            'updated_at' => $params['updated_at'],
            'tournament_id' => $params['tournament_id'],
            'transaction_sub_type' => $params['transaction_sub_type'],
            'tournament_buy_in' => $params['tournament_buy_in'],
            'tournament_entryFee' => $params['tournament_entryFee'],
            'tournament_bounty_knockout' => $params['tournament_bounty_knockout']
        ]);

        $this->utils->debug_log('ADJUST_WALLET_REFUND ' . __METHOD__ , $this->db->last_query());

        if($inserted === false){
            return false;
        }

        return $this->db->insert_id();
    }

    public function updateTransaction($transaction_id, $status){
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

    public function updateOriginalBetToRefundStatus($round_id, $status){
        $updated = $this->db->where('round_id', $round_id)
        ->where('status', $status)
        ->set([
                'status' => 'REFUND',
                'flag_of_updated_result' => 1
            ])
        ->update($this->tableName);

        $this->utils->debug_log('Evenbet' . __METHOD__ , $this->db->last_query());

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