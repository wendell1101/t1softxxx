<?php
if(! defined("BASEPATH")){
    exit("No direct script access allowed");
}

require_once dirname(__FILE__) . "/base_model.php";

class Sbobet_seamless_game_transactions_model extends BaseModel
{

    protected $tableName = "sbobet_seamless_game_transactions";

    public function __construct()
    {
        parent::__construct();
    }

    /** 
     * Insert Transaction
     * 
     * @param array $data
     * 
     * @return int
    */
    public function insertTransaction($data)
    {
       $this->db->insert($this->tableName,$data);
       return $this->db->affected_rows();
    }

    /** 
     * Get TransactionId By TransferCode
     * 
     * @param int $transferCode
     * @param int $transactionType
     * 
     * @return string
    */
    public function getTransactionIdByTransferCode($transferCode,$transactionType)
    {
        $this->db->select_max("transaction_id")
                ->from($this->tableName)
                    ->where("transfer_code",$transferCode)
                        ->where("transaction_type",$transactionType);

        return $this->runOneRowOneField('transaction_id');
    }

    /** 
     * Get last round (TransactionId) of TransferCode
     * 
     * @param int $transferCode
     * 
     * @return string
    */
    public function getLastTransactionIdOfTransferCode($transferCode)
    {
        $this->db->select_max("transaction_id",'last_round')
                ->from($this->tableName)
                    ->where("transfer_code",$transferCode);

        return $this->runOneRowOneField('last_round');
    }

    public function getTransactionIdsByTransferCode($transferCode){
        $this->db->distinct();
        $this->db->select("transaction_id")
                ->from($this->tableName)
                    ->where("transfer_code",$transferCode);

        $rounds =  $this->runMultipleRowArray();
        return array_column($rounds, 'transaction_id');
    }

    public function getTotalBetByTransferCode($transferCode){
        $this->db->select_sum("amount")
                ->from($this->tableName)
                    ->where("transfer_code",$transferCode)
                        ->where("transaction_type",'DeductDeduct');
        return $this->runOneRowOneField('amount');
    }

    public function isCancelBetTransaction($transferCode, $transactionId){
        $this->db->select('transaction_id');
        $this->db->from($this->tableName)
        ->where("transfer_code",$transferCode)
        ->where("transaction_id",$transactionId)
        ->where("transaction_type",'CancelAdd');
        return $this->runExistsResult();
    }
}