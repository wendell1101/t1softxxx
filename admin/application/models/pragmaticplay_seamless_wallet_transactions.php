<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

class Pragmaticplay_seamless_wallet_transactions extends BaseModel {

    const TRANSACTION_ROLLBACK = 'rollback';
    const TRANSACTION_CANCELLED = 'cancel';

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';

    function __construct() {
        parent::__construct();
    }

    public $tableName = "pragmaticplay_seamless_wallet_transactions";

    public function searchByExternalTransactionIdByTransactionType($transaction_id, $table_name = null) {
        if (!empty($table_name)) {
            $this->tableName = $table_name;
        }

        $this->db->from($this->tableName)->where('transaction_id', $transaction_id)
            ->order_by('created_at', 'asc');

        $query = $this->db->get();

        if ($query && $query->num_rows() >= 1) {
            return $query->result_array();
        }
        else {
            return [];
        }
    }

    public function cancelTransaction($transaction_id, $table_name = null, $with_result = false) {
        if (!empty($table_name)) {
            $this->tableName = $table_name;
        }

        $this->db->where('transaction_id', $transaction_id)
            ->set([
                'transaction_type' => self::TRANSACTION_CANCELLED
            ]);

        $this->runAnyUpdate($this->tableName, null, $with_result);
    }

    public function insertTransaction($params, $table_name = null) {
        if (!empty($table_name)) {
            $this->tableName = $table_name;
        }

        $inserted = $this->db->insert($this->tableName, [
            'user_id' => $params['user_id'],
            'game_id' => $params['game_id'],
            'round_id' => $params['round_id'],
            'amount' => isset($params['amount'])?$params['amount']:0,
            'transaction_id' => $params['transaction_id'],
            'transaction_type' => $params['transaction_type'],
            'provider_id' => $params['provider_id'],
            'timestamp' => $params['timestamp'],
            'round_details' => $params['round_details'],
            'jackpot_id' => $params['jackpot_id'],
            'before_balance' => $params['before_balance'],
            'after_balance' => $params['after_balance'],
            'campaign_id' => $params['campaign_id'],
            'campaign_type' => $params['campaign_type'],
            'currency' => $params['currency'],
            'external_uniqueid' => $params['external_uniqueid'],
        ]);

        if($inserted === false){
            return false;
        }

        return $this->db->insert_id();
    }

    public function updateResponseResultId($wallet_transaction_id, $response_result_id, $table_name = null, $with_result = false) {
        if (!empty($table_name)) {
            $this->tableName = $table_name;
        }

        $this->db->where('id', $wallet_transaction_id)
            ->set([
                'response_result_id' => $response_result_id,
                'request_id' => $this->utils->getRequestId()
            ]);

        $this->runAnyUpdate($this->tableName, null, $with_result);
    }

    public function isPlayerTransactionAlreadyProcessedByTypeUserGameRound($transaction_type, $user_id, $game_id, $round_id, $table_name = null)
    {
        if (!empty($table_name)) {
            $this->tableName = $table_name;
        }

        $this->CI->db->from($this->tableName)
        ->where('transaction_type', $transaction_type)
        ->where('user_id', $user_id)
        ->where('game_id', $game_id)
        ->where('round_id', $round_id);

        return $this->runExistsResult();
    }

    public function isPlayerTransactionAlreadyProcessedByTypeUserGameRoundCustom($fields = [], $table_name = null) {
        if (!empty($table_name)) {
            $this->tableName = $table_name;
        }

        $this->CI->db->from($this->tableName)->where($fields);
        return $this->runExistsResult();
    }

    public function queryPlayerTransactionsByRound($user_id, $game_id, $round_id, $table_name = null) 
    {
        if (!empty($table_name)) {
            $this->tableName = $table_name;
        }

        $this->db->from($this->tableName)
        ->where('user_id', $user_id)
        ->where('game_id', $game_id)
        ->where('round_id', $round_id)
        ->order_by('created_at', 'asc');

        return $this->runMultipleRowArray();
    }

    public function queryPlayerTransactionsByRoundAndType($user_id, $game_id, $round_id, $transaction_type, $table_name = null) 
    {
        if (!empty($table_name)) {
            $this->tableName = $table_name;
        }

        $this->db->from($this->tableName)
        ->where('user_id', $user_id)
        ->where('game_id', $game_id)
        ->where('round_id', $round_id)
        ->where('transaction_type', $transaction_type);

        return $this->runOneRowArray();
    }

    public function queryPlayerBetTransactionByRoundId($user_id, $round_id, $transaction_type, $table_name = null) 
    {
        if (!empty($table_name)) {
            $this->tableName = $table_name;
        }

        $this->db->from($this->tableName)
        ->where('user_id', $user_id)
        ->where('round_id', $round_id)
        ->where('transaction_type', $transaction_type);

        return $this->runOneRowArray();
    }

    public function updateSettledAt($roundId, $userId, $dateTime = null, $table_name = null, $with_result = false) {
        if (!empty($table_name)) {
            $this->tableName = $table_name;
        }

        if(empty($dateTime)){
            $dateTime = date('Y-m-d H:i:s');
        }
        $this->db
            ->where('round_id', $roundId)
            ->where('user_id', $userId)
            ->set([
                'settled_at' => date('Y-m-d H:i:s', strtotime($dateTime))
            ]);

        $this->runAnyUpdate($this->tableName, null, $with_result);
    }
}