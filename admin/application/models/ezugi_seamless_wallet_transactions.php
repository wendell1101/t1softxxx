<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

class Ezugi_seamless_wallet_transactions extends BaseModel {

    const TRANSACTION_ROLLBACK = 'rollback';
    const TRANSACTION_CANCELLED = 'cancel';

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';

    function __construct() {
        parent::__construct();
    }

    public $tableName = "ezugi_seamless_wallet_transactions";


    public function searchByTransactionIdAndTransactionType($transaction_id, $transaction_type = null, $table_name = null) {
        if (empty($table_name)) {
            $table_name = $this->tableName;
        }

        $this->db->from($table_name)
            ->where('transaction_id', $transaction_id);

        if($transaction_type != null) {
            $this->db->where('transaction_type', $transaction_type);
        }
        $query = $this->db->get();

        return $this->getMultipleRow($query);
    }

    public function getTransactionObjectByField($transaction_id, $transaction_type = null, $table_name = null) {
        if (empty($table_name)) {
            $table_name = $this->tableName;
        }

        $this->db->from($table_name)
        ->where('transaction_id', $transaction_id);

        if($transaction_type != null) {
            $this->db->where('transaction_type', $transaction_type);
        }
        $query = $this->db->get();
        return $this->getOneRow($query);
    }

    public function updateTransaction($game_platform_id, $external_unique_id, $data, $table_name = null){
        if (empty($table_name)) {
            $table_name = $this->tableName;
        }

        $this->db->where("external_unique_id", $external_unique_id);
        $this->db->where("game_platform_id", $game_platform_id);
        $this->db->update($table_name,$data);
        if ($this->db->affected_rows() == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function cancelTransaction($transaction_id, $table_name = null) {
        if (empty($table_name)) {
            $table_name = $this->tableName;
        }

        $this->db->where('transaction_id', $transaction_id)
            ->set([
                'transaction_type' => self::TRANSACTION_CANCELLED
            ]);

        $this->runAnyUpdate($table_name);
    }

    public function insertRow($data, $db=null) {
        if(empty($db) || !is_object($db)){
            $db=$this->db;
        }
        $qry=$db->insert($this->tableName, $data);

        if($qry===false){
            return false;
        }

        return $db->insert_id();
    }
        
}