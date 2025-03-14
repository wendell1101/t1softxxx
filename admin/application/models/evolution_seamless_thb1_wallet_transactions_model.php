<?php
if(! defined("BASEPATH")){
    exit("No direct script access allowed");
}

require_once dirname(__FILE__) . "/base_model.php";

class Evolution_seamless_thb1_wallet_transactions_model extends BaseModel
{

    public $tableName = "evolution_seamless_thb1_wallet_transactions";

    public function __construct()
    {
        parent::__construct();

    }

    public function setTableName($tablename){
        $this->tableName=$tablename;
    }

    /** 
     * check if transaction ID is already exist
     * 
     * @param int $transactionId
     * 
     * @return boolean
    */
    public function isTransactionAlreadyExist($transactionId)
    {
        $this->db->from($this->tableName)
                ->where("transactionId",$transactionId);

        return $this->runExistsResult();
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
     * Check if Transaction is already refunded
     * 
     * @param int $transactionId
     * 
     * @return boolean
    */
    public function isAlreadyRefunded($transactionId){

        $this->db->from($this->tableName)
                ->where("transactionId",$transactionId)
                ->where("refundedIn is NOT NULL");

        return $this->runExistsResult();
    }

    /** 
     * Check if Transaction is already settled
     * 
     * @param int $transactionId
     * 
     * @return boolean
    */
    public function isAlreadySettled($transactionId){

        $this->db->from($this->tableName)
                ->where("transactionRefId",$transactionId)
                ->where("action", 'credit');

        return $this->runExistsResult();
    }

    /** 
     * Check if Transaction is already cancelled
     * 
     * @param int $transactionId
     * 
     * @return boolean
    */
    public function isAlreadyCancelled($transactionId){

        $this->db->from($this->tableName)
                ->where("transactionRefId",$transactionId)
                ->where("action", 'cancel');

        return $this->runExistsResult();
    }

    /** 
     * Update Refunded Transaction
     * 
     * @param int $refunded_transaction_id
     * 
     * @return int
     * 
    */
    public function updateRefundedTransaction($refunded_transaction_id)
    {

        $this->db->where("transactionId",$refunded_transaction_id)
            ->set([
                "refundedIn" => $this->CI->utils->getNowForMysql()
            ]);

        $this->runAnyUpdate($this->tableName);

        return $this->db->affected_rows();
    }

    /**
     * Get Amount in Transaction based in transaction id
     * 
     * @param string $transactionId
     * 
     * @return string
     */
    public function getTransactionAmount($transactionId)
    {
        $query = $this->db->select("transactionAmount")
                    ->from($this->tableName)
                    ->where("transactionId",$transactionId)
                    ->get();

         return $this->getOneRowOneField($query,"transactionAmount");
    }

    /**
     * Get After Balance based in unique id
     * 
     * @param string $transactionId
     * 
     * @return int
     */
    public function getAfterBalance($transactionId,$username)
    {
        $query = $this->db->select("afterBalance")
                    ->from($this->tableName)
                    ->where("gameId",$transactionId)
                    ->where("userId",$username)
                    ->where("action","credit")
                    ->order_by('id','desc')
                    ->get();

         return $this->getOneRowOneField($query,"afterBalance");
    }

	public function getRoundData($table, $whereParams) {
		
		if(empty($whereParams)){
			return false;
		}
		
		$query = $this->db->get_where($table, $whereParams);
		return $query->result_array();
	
	}

	public function getTransactionData($table, $whereParams) {
		
		if(empty($whereParams)){
			return false;
		}
		
		$query = $this->db->get_where($table, $whereParams);
        return $this->getOneRowArray($query);
	
	}

}