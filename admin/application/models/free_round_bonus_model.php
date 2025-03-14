<?php
require_once dirname(__FILE__) . '/base_model.php';

class Free_round_bonus_model extends BaseModel {
    protected $tableName = 'free_round_bonuses';

    const TRANSACTION_CREATED = 'created';
    const TRANSACTION_CANCELLED = 'cancelled';

    function __construct() {
        parent::__construct();
    }

    public function insertTransaction($params) {
        $params['extra'] = json_encode($params['extra']);
        $params['status'] = self::TRANSACTION_CREATED;
        $inserted = $this->db->insert($this->tableName, $params);
        return $inserted;
    }

    function cancelTransaction($transaction_id, $platformCode) {
        $this->db->where('transaction_id', $transaction_id);
        $this->db->where('game_platform_id', $platformCode);
        $this->db->set([
            'status' => self::TRANSACTION_CANCELLED
        ]);

        return $this->runAnyUpdate($this->tableName);
    }

    function updateFreeRound($data, $transaction_id, $platformCode) {
        $this->db->where('transaction_id', $transaction_id);
        $this->db->where('game_platform_id', $platformCode);
        $this->db->set($data);

        return $this->runAnyUpdate($this->tableName);
    }


	public function queryTransaction($transaction_id, $platformCode) {
		$this->db->from($this->tableName)->where([
            'transaction_id' => $transaction_id,
            'game_platform_id' => $platformCode
        ]);

		return $this->runOneRowArray();
	}
}