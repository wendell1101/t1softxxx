<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

class Jumbo_seamless_wallet_transactions extends BaseModel {

    const TRANSACTION_ROLLBACK = 'rollback';
    const TRANSACTION_CANCELLED = 'cancel';

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';

    function __construct() {
        parent::__construct();
    }

    public $tableName = "jumbo_seamless_wallet_transactions";

    public function searchByExternalTransactionIdByTransactionType($transaction_id, $custom_table = null) {
        if(!empty($custom_table)){
            $this->tableName = $custom_table;
        }
        $transaction_id=(string)$transaction_id;
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

    public function searchByExternalTransactionByTransactionIdAndStatus($transaction_id, $status, $custom_table = null) {
        if(!empty($custom_table)){
            $this->tableName = $custom_table;
        }

        $transaction_id=(string)$transaction_id;
        $this->db->from($this->tableName)
            ->where('transaction_id', $transaction_id)
            ->where('game_status', $status);

        $query = $this->db->get();

        if ($query && $query->num_rows() >= 1) {
            return $query->result_array();
        }
        else {
            return [];
        }

    }

    public function getExistingTransactionByRefTransferId($table, $transaction_id) {
        $transaction_id=(string)$transaction_id;
        $qry = $this->db->get_where($table, array('transaction_id' => $transaction_id));
        return $this->getOneRow($qry);
	}

    public function checkTransactionSettled($transaction_id, $custom_table = null){
        if(!empty($custom_table)){
            $this->tableName = $custom_table;
        }
        $transaction_id=(string)$transaction_id;
        $this->db->select('transaction_id,game_status');
        $this->db->from($this->tableName)->where('transaction_id', $transaction_id)
            ->order_by('created_at', 'asc');

        $query = $this->db->get();

        if ($query && $query->num_rows() >= 1) {
            return $query->row();
        }
        else {
            return [];
        }
    }

    public function cancelTransaction($transaction_id, $custom_table = null) {
        if(!empty($custom_table)){
            $this->tableName = $custom_table;
        }
        $transaction_id=(string)$transaction_id;
        $this->db->where('transaction_id', $transaction_id)
            ->set([
                'transaction_type' => self::TRANSACTION_CANCELLED
            ]);

        $this->runAnyUpdate($this->tableName);
    }

    public function insertTransaction($params, $custom_table = null) {
        if(!empty($custom_table)){
            $this->tableName = $custom_table;
        }
        $inserted = $this->db->insert($this->tableName, [
            'game_platform_id' => $params['game_platform_id'],
            'amount' => $params['amount'],
            'before_balance' => $params['before_balance'],
            'after_balance' => $params['after_balance'],
            'player_id' => $params['player_id'],
            'game_id' => $params['game_id'],
            'transaction_type' => $params['transaction_type'],
            'game_status' => $params['game_status'],
            'response_result_id' => $params['game_status'],
            'external_unique_id' => $params['external_unique_id'],
            'start_at' => $params['start_at'],
            'end_at' => $params['end_at'],
            'created_at' => $params['created_at'],
            'updated_at' => $params['updated_at'],
            'transaction_id' => $params['transaction_id'],
            'round_id' => $params['round_id'],
            'bet_amount' => $params['bet_amount'],
            'result_amount' => $params['result_amount'],
            'game_seq_no' => $params['game_seq_no'],
            'game_type' => isset($params['game_type']) ? $params['game_type'] : 0,
            'report_date' => isset($params['report_date']) ? $params['report_date'] : '',
            'currency' => $params['currency'],
            'win_amount' => isset($params['win_amount']) ? $params['win_amount'] : '',
            'net_win' => isset($params['net_win']) ? $params['net_win'] : '',
            'demon' => isset($params['denom']) ? $params['denom'] : '',
            'client_type' => isset($params['client_type']) ? $params['client_type'] : '',
            'system_take_win' => isset($params['system_take_win']) ? $params['system_take_win'] : '',
            'jackpot_win' => isset($params['jackpot_win']) ? $params['jackpot_win'] : '',
            'jackpot_contribute' => isset($params['jackpot_contribute']) ? $params['jackpot_contribute'] : '',
            'has_free_game' => isset($params['has_free_game']) ? $params['has_free_game'] : '',
            'has_gameble' => isset($params['has_gamble']) ? $params['has_gamble'] : '',
            'extra_info' => isset($params['extra_info']) ? $params['extra_info'] : null,
            'valid_bet' => isset($params['valid_bet']) ? $params['valid_bet'] : null,
            'ref_transfer_id' => isset($params['ref_transfer_id']) ? $params['ref_transfer_id'] : null,
            'historyId' => isset($params['historyId']) ? $params['historyId'] : null,
        ]);

        if($inserted === false){
            return false;
        }

        return $this->db->insert_id();
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

    public function updateStatusByTransactionId($status, $external_unique_id, $custom_table = null){
        if(!empty($custom_table)){
            $this->tableName = $custom_table;
        }
        $this->db->where('external_unique_id', (string)$external_unique_id)
            ->set([
                'game_status' => $status
            ]);

        $this->runAnyUpdate($this->tableName);
    }
}