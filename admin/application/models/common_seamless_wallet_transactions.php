<?php
if(! defined("BASEPATH")){
    exit("No direct script access allowed");
}

require_once dirname(__FILE__) . "/base_game_logs_model.php";

class Common_seamless_wallet_transactions extends Base_game_logs_model
{

    public $tableName = "common_seamless_wallet_transactions";

    const MD5_FIELDS_FOR_ORIGINAL = [
        'game_platform_id',
        'amount',
        'before_balance',
        'after_balance',
        'player_id',
        'game_id',
        'transaction_type',
        'status',
        'response_result_id',
        'external_unique_id',
        'extra_info',
        'round_id',
        'end_at'
    ];
    const ROUND_FINISHED = 1;

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'before_balance',
        'after_balance',
        'amount'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getGameLogStatistics($dateFrom, $dateTo)
    {
        return false;
    }

    public function setTransactionTable($table)
    {
        $this->tableName = $table;
    }

    /**
     * Overview: Get all Rows within game platform with filter data
     *
     * @param  int $gamePlatformID
     * @param datetime $dataFrom
     * @param datetime $dateTo
     * @param string $byDate
     *
     * @return array
     */
    public function getTransactions($gamePlatformID, $dateFrom, $dateTo, $byDate = 'updated_at') {
        $sqlTime = "`$byDate` >= ? AND `$byDate` <= ?";

        $sql = <<<EOD
SELECT
    *
FROM {$this->tableName}
WHERE
game_platform_id = ? AND
{$sqlTime};
EOD;

        $params=[
            $gamePlatformID,
            $dateFrom,
            $dateTo
        ];

        return $this->runRawSelectSQLArray($sql, $params);
    }

    /**
     * Overview: check transaction if exist based in unique id
     *
     * @param int $gamePlatformId
     * @param int $externalUniqueId
     *
     * @return boolean
    */
    public function isTransactionExist($gamePlatformId, $externalUniqueId, $customTable= null)
    {
        if(!empty($customTable)){
            $this->tableName = $customTable;
        }
        $this->db->from($this->tableName)
            ->where("game_platform_id",$gamePlatformId)
            ->where('external_unique_id',$externalUniqueId);

        return $this->runExistsResult();
    }

    /**
     * Overview: check transaction if exist based custom unique id
     *
     * @param int $gamePlatformId
     * @param int $externalUniqueId
     * @param string $field the field in where class
     *
     * @return boolean
    */
    public function isTransactionExistCustom($gamePlatformId,$externalUniqueId,$field='transaction_id', $custom_where = [])
    {
        $this->db->from($this->tableName)
            ->where("game_platform_id",$gamePlatformId);

            if (!empty($custom_where)) {
                $this->db->where($custom_where);
            } else {
                $this->db->where($field,$externalUniqueId);
            }

        return $this->runExistsResult();
    }

    public function get_transaction($where = [], $selectedColumns = [], 
        $order_by = ['field_name' => '', 'is_desc' => false]
    ) {
        if (!empty($selectedColumns) && is_array($selectedColumns)) {
            $columns = implode(",", $selectedColumns);
            $this->db->select($columns);
        }

        $this->db->from($this->tableName)->where($where);

        if (!empty($order_by['field_name'])) {
            $sort = isset($order_by['is_desc']) && $order_by['is_desc'] ? 'DESC' : 'ASC';
            $this->db->order_by($order_by['field_name'], $sort);
        }

        return $this->runOneRowArray();
    }

	public function getTransactionRowArray($gamePlatformId, $externalUniqueId, $customTable = null) {
        if(!empty($customTable)){
            $this->tableName = $customTable;
        }
        $this->db->from($this->tableName)
        ->where("game_platform_id",$gamePlatformId)
        ->where('external_unique_id',$externalUniqueId);
		$query = $this->db->get();
        return $query->row_array();
	}

    public function getTransactionRowObject($gamePlatformId, $externalUniqueId) {
        $query = $this->db->from($this->tableName)
        ->where("game_platform_id",$gamePlatformId)
        ->where('external_unique_id',$externalUniqueId)
        ->get();
        return $this->getOneRow($query);
    }

    /**
     * Check if Transaction is already refunded
     *
     * @param int $transactionId
     * @param int $uniqueId
     *
     * @return boolean
    */
    public function isTransactionAlreadyRefunded($transactionId,$uniqueId='external_unique_id'){

        $sql = <<<EOD
SELECT
    JSON_UNQUOTE(extra_info->'$.isRefunded') AS isRefunded
FROM {$this->tableName}
WHERE
{$uniqueId} = ?
EOD;

        $params=[
            $transactionId,
        ];

        $isRefunded = $this->runRawSelectSQLArray($sql, $params);
        $iR = isset($isRefunded[0]['isRefunded']) ? $isRefunded[0]['isRefunded'] : false;
        $r = $iR == 'true' ? true : false;
        return $r;
    }

    /**
     * check if bet has refund already
     *
     * @param int $gamePlatformId
     * @param int $id
     * @param string $field
     * @return boolean
    */
    public function checkIfBetHaveRefund($gamePlatformId,$id,$field='abortedTransactionRequestId'){

        $sql = <<<EOD
SELECT
    JSON_UNQUOTE(extra_info->'$.{$field}') AS `field`
FROM {$this->tableName}
WHERE
game_platform_id = ?
HAVING field = ?
EOD;

        $params=[
            $gamePlatformId,
            $id
        ];

        $data = $this->runRawSelectSQLArray($sql, $params);
        $field = isset($data[0]['field']) ? $data[0]['field'] : null;
        $status = !is_null($field) ? true : false;

        return $status;
    }

    /**
     * update Transaction to refunded
     *
     * @param int $transactionId
     * @param int $uniqueId
     *
     * @return int
    */
    public function updateRefundedTransaction($transactionId,$uniqueId='external_unique_id'){

        $sql = <<<EOD
UPDATE {$this->tableName}
    SET extra_info = JSON_SET(extra_info, "$.isRefunded",true)
WHERE
{$uniqueId} = ?
EOD;

        $params=[
            $transactionId,
        ];

        $query = $this->runRawUpdateInsertSQL($sql, $params);

        return $query;
    }

    /**
     * update Transaction to refunded
     *
     * @param int $transactionId
     * @param int $uniqueId
     *
     * @return int
    */
    public function setTransactionStatus($gamePlatformId, $uniqueIdValue, $uniqueId='external_unique_id', $status='ok'){

        if(empty($gamePlatformId) || empty($uniqueIdValue)){
            return false;
        }

        $sql = <<<EOD
UPDATE {$this->tableName}
    SET status = ?
WHERE
game_platform_id = ? and {$uniqueId} = ?
EOD;

        $params=[
            $status,
            $gamePlatformId,
            $uniqueIdValue,
        ];

        $query = $this->runRawUpdateInsertSQL($sql, $params);

        return $query;
    }

    /**
     * Insert Transaction
     *
     * @param array $data
     *
     * @return mixed
    */
    public function insertTransaction($data)
    {
        $this->CI->load->model([
            'original_game_logs_model'
        ]);

        try{
            list($insertRows, $updatedRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->tableName,
                $data,
                'external_unique_id',
                'external_unique_id',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            if (!empty($insertRows)) {
                $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
            }


            if (!empty($updatedRows)) {
                $this->updateOrInsertOriginalGameLogs($updatedRows, 'update');
            }

            return [
                'insertedRows' => $insertRows,
                'updatedRows' => $updatedRows
            ];
            unset($insertRows);
            unset($updateRows);
        }catch(\Exception $e){
            $this->CI->utils->debug_log(__METHOD__. "ERROR in inserting transaction with data: ",$data);
            return false;
        }
    }

    protected function updateOrInsertOriginalGameLogs($data, $queryType)
    {
        $this->CI->load->model([
            'original_game_logs_model'
        ]);

        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                	$record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->tableName, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->tableName, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }


    /**
     * Get Amount field based in transaction_id index field
     *
     * @param int $transactionId
     * @param string|bet $type
     *
     * @return string
    */
    public function getAmountBasedInTransactionId($transactionId,$type='bet')
    {
        $query = $this->db->select("amount")
                    ->from($this->tableName)
                    ->where("transaction_id",$transactionId)
                    ->where("transaction_type",$type)
                    ->get();

         return $this->getOneRowOneField($query,"amount");
    }

    /**
     * Get The round id of record base in updated_at column
     *
     * @param datetime $dateFrom
     * @param datetime $dateTo
     * @param int $game_provider_id
     *
     * @return arrray
     */
    public function getRoundIDS($dateFrom,$dateTo,$game_provider_id)
    {
        $query = $this->db->select("cs.round_id,cs.player_id")
                    ->distinct()
                    ->from($this->tableName." cs")
                    ->join("game_provider_auth gpa","gpa.player_id = cs.player_id")
                    ->where("cs.updated_at >=",$dateFrom)
                    ->where("cs.updated_at <=",$dateTo)
                    ->where("gpa.game_provider_id",$game_provider_id)
                    ->get();

        return $this->getMultipleRowArray($query);
    }

    /**
     * Get Game records by rounds, using Batch method
     *
     * @param array $round_id
     * @param int $game_provider_id
     *
     * @return array
    */
    public function getTransactionsByRoundIds($round_id,$game_provider_id)
    {
        if(is_array($round_id) && count($round_id) > 0){
            $ids = implode("','",$round_id);

            $this->CI->load->model([
                'original_game_logs_model'
            ]);

            $where="cs.round_id IN('{$ids}') and gpa.game_provider_id = ?";

        $sql = <<<EOD
SELECT cs.id as sync_index,
cs.game_platform_id,
cs.amount,
cs.before_balance,
cs.after_balance,
cs.player_id,
cs.game_id,
cs.transaction_type,
cs.external_unique_id as external_uniqueid,
cs.extra_info,
cs.start_at,
cs.end_at,
cs.transaction_id,
cs.round_id,
cs.md5_sum,
cs.response_result_id,
gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id,
gpa.login_name as player_username,
gpa.player_id

FROM {$this->tableName} as cs
LEFT JOIN game_description as gd ON gd.external_game_id = cs.game_id and gd.game_platform_id = ?
LEFT JOIN game_type ON gd.game_type_id = game_type.id
LEFT JOIN game_provider_auth as gpa ON gpa.player_id = cs.player_id and gpa.game_provider_id = ?
WHERE
{$where}
ORDER BY cs.end_at ASC

EOD;

            $params=[$game_provider_id,$game_provider_id,$game_provider_id];

            return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        }

        return array();
    }

    /**
     * Get After Balance based in unique id
     *
     * * for AG Seamless, it not possible to get two result in one round
     *
     * @param string $transactionId
     * @param string $uniqueId
     * @param string $comparisonOperator
     * @param string $transactionTypeValue
     *
     * @return int
     */
    public function getAfterBalance($transactionId,$uniqueId="transaction_id",$comparisonOperator="!=",$transactionTypeValue="bet")
    {
        $query = $this->db->select("after_balance")
                    ->from($this->tableName)
                    ->where($uniqueId,$transactionId)
                    ->where("transaction_type {$comparisonOperator}",$transactionTypeValue)
                    ->get();

         return $this->getOneRowOneField($query,"after_balance");
    }

    public function getTableName(){
        return $this->tableName;
    }

    public function updateTransaction($game_platform_id, $external_unique_id, $data){
        $this->db->where("external_unique_id", $external_unique_id);
        $this->db->where("game_platform_id", $game_platform_id);
        $this->db->update($this->tableName,$data);
        if ($this->db->affected_rows() == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function getTransIdRowArray($gamePlatformId, $trans_id, $transaction_type) {
        $this->db->from($this->tableName)
        ->where("game_platform_id",$gamePlatformId)
        ->where('transaction_id',$trans_id)
        ->where('transaction_type', $transaction_type);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function getRoundRowArray($gamePlatformId, $game_id, $transaction_type) {
        $this->db->from($this->tableName)
        ->where("game_platform_id",$gamePlatformId)
        ->where('game_id',$game_id)
        ->where('transaction_type',$transaction_type);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function getTransactionObjectsByField($game_platform_id, $data = [], $field = 'external_unique_id', $transaction_type = null) {
        $this->db->from($this->tableName)
            ->where("game_platform_id", $game_platform_id)
            ->where_in($field, $data);

        if($transaction_type != null) {
            $this->db->where('transaction_type', $transaction_type);
        }
        $query = $this->db->get();

        return $this->getMultipleRow($query);
    }

    public function getTransactionObjectByField($game_platform_id, $data, $field = 'external_unique_id', $transaction_type = null) {
        $this->db->from($this->tableName)
            ->where("game_platform_id", $game_platform_id)
            ->where($field, $data);

        if($transaction_type != null) {
            $this->db->where('transaction_type', $transaction_type);
        }
        $query = $this->db->get();
        return $this->getOneRow($query);
    }

    public function delete($field = null, $data = null) {
        if($field == null || $data == null) {
            return false;
        }
        $this->db->where($field, $data);
        $success = $this->runRealDelete($this->tableName, $this->db);
        return $success;
    }

    public function generateMD5Transaction($data){
        return $this->generateMD5SumOneRow($data, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
    }

    public function isRoundEnded($gamePlatformId, $playerId, $roundId, $statusIdentifierForRoundEnded = self::ROUND_FINISHED){
        $this->db->select("status")
            ->from($this->tableName)
            ->where("game_platform_id",$gamePlatformId)
            ->where("player_id",$playerId)
            ->where("round_id",$roundId)
            ->where("status",$statusIdentifierForRoundEnded);

        return $this->runExistsResult();
    }

    // public function isPlayerHaveOpenedRounds($gamePlatformId, $playerId){

    //     #get last round
    //     $query = $this->db->select_max("round_id")
    //                 ->from($this->tableName)
    //                 ->where("game_platform_id", $gamePlatformId)
    //                 ->where("player_id",$playerId)
    //                 ->get();

    //     $roundId = $this->getOneRowOneField($query,"round_id");
    //     if(!empty($roundId)){
    //         $isRoundEnded = $this->isRoundEnded($gamePlatformId, $playerId, $roundId);
    //         if(!$isRoundEnded){
    //             return true;
    //         }
    //     }
    //     return false;
    // }

    public function isPlayerRoundGameCodeExist($gamePlatformId, $playerId, $round, $gameCode = null)
    {
        $this->db->from($this->tableName)
            ->where("game_platform_id",$gamePlatformId)
            ->where('player_id',$playerId)
            ->where('round_id',$round);
            if(!empty($gameCode)){
                $this->db->where('game_id',$gameCode);
            }

        return $this->runExistsResult();
    }

    public function getResultAmountOfRound($gamePlatformId, $playerId, $round){
        $query = $this->db->select_sum("result_amount")
                    ->from($this->tableName)
                    ->where("game_platform_id", $gamePlatformId)
                    ->where("player_id", $playerId)
                    ->where("round_id", $round)
                    ->get();

        return  $this->getOneRowOneField($query,"result_amount");
    }

    public function updatePlayerGameRoundStatus($game_platform_id, $playerId, $gameCode){
        $this->db->where("player_id", $playerId);
        $this->db->where("game_platform_id", $game_platform_id);
        $this->db->where("game_id", $gameCode);
        $success =  $this->runUpdate(['status' => self::ROUND_FINISHED]);
        return $success;
        // $this->db->update($this->tableName,['status' => self::ROUND_FINISHED]);
        // if ($this->db->affected_rows() > 0) {
        //     return true;
        // } else {
        //     return false;
        // };
    }

    public function getLastStatusOfRound($gamePlatformId, $playerId, $roundId){
        $query = $this->db->select_max("id")
                    ->from($this->tableName)
                    ->where("game_platform_id", $gamePlatformId)
                    ->where("player_id",$playerId)
                    ->where("round_id",$roundId)
                    ->get();

        $maxID = $this->getOneRowOneField($query,"id");
        
        $query2 = $this->db->select("status")
                    ->from($this->tableName)
                    ->where("id",$maxID)
                    ->get();

        $status = $this->getOneRowOneField($query2,"status");
        return $status;
    }

    public function getPlayerLastRound($gamePlatformId, $playerId, $ignoreGameCode = []){

        #get last round
        $this->db->select_max("round_id");
        $this->db->from($this->tableName);
        $this->db->where("game_platform_id", $gamePlatformId);
        $this->db->where("player_id",$playerId);
        if (!empty($ignoreGameCode)) {
            $this->db->where_not_in("game_id", $ignoreGameCode);
        }
        $query = $this->db->get();
        // echo $this->db->last_query();exit();

        $roundId = $this->getOneRowOneField($query,"round_id");
        return $roundId;
    }

    public function getPlayerIdByRound($gamePlatformId, $roundId){
        $query = $this->db->from($this->tableName)
        ->where("game_platform_id",$gamePlatformId)
        ->where('round_id',$roundId)
        ->get();
        // return $this->getOneRow($query);
        return  $this->getOneRowOneField($query,"player_id");
    }

    public function getGameIdByRound($gamePlatformId, $roundId){
        $query = $this->db->from($this->tableName)
        ->where("game_platform_id",$gamePlatformId)
        ->where('round_id',$roundId)
        ->get();
        return  $this->getOneRowOneField($query,"game_id");
    }

    public function updateResponseResultId($transactionId, $resposeResultId) {
		$data = ['response_result_id'=>$resposeResultId];
		return $this->updateData('id', $transactionId, $this->tableName, $data);
	}

    public function getSumAmountByTransactionAndTransType($gamePlatformId, $transactionId, $transactionType){
        $query = $this->db->select_sum("amount")
                    ->from($this->tableName)
                    ->where("game_platform_id", $gamePlatformId)
                    ->where("transaction_id", $transactionId)
                    ->where("transaction_type", $transactionType)
                    ->get();

        return  $this->getOneRowOneField($query,"amount");
    }

    public function getTransactionObjects($game_platform_id, $where = []) {
        $this->db->from($this->tableName)
            ->where("game_platform_id", $game_platform_id);

        foreach($where as $key => $value) {
            $this->db->where_in($key, $value);
        }
        $query = $this->db->get();

        return $this->getMultipleRow($query);
    }

	public function getRoundData($whereParams) {
		
		if(empty($whereParams)){
			return false;
		}
		
		$query = $this->db->get_where($this->tableName,$whereParams);

		return $query->result_array();
	
	}

    public function getCustomLastRecord($gamePlatformId, $playerId, $roundId, $transactionType, $customFields = ["status"]){
        $query = $this->db->select_max("id")
                    ->from($this->tableName)
                    ->where("game_platform_id", $gamePlatformId)
                    ->where("player_id",$playerId)
                    ->where("round_id",$roundId)
                    ->where("transaction_type",$transactionType)
                    ->get();

        $maxID = $this->getOneRowOneField($query,"id");

        $customFields = implode(",", $customFields);
        $query2 = $this->db->select($customFields)
                    ->from($this->tableName)
                    ->where("id",$maxID)
                    ->get();

        $row = $this->getOneRow($query2);
        return $row;
    }

    public function getSumAmountByRoundId($gamePlatformId, $playerId, $roundId, $transactionType, $dateFrom = null){
        $this->db->select_sum("amount");
        $this->db->from($this->tableName);
        $this->db->where("game_platform_id", $gamePlatformId);
        $this->db->where("player_id",$playerId);
        $this->db->where("round_id", $roundId);
        $this->db->where("transaction_type", $transactionType);
        if(!empty($dateFrom)){
            $this->db->where("created_at >=", $dateFrom);
        }
        $query = $this->db->get();

        return  $this->getOneRowOneField($query,"amount");
    }

    public function getAllRelatedTransactionObjects($game_platform_id, $where = []) {
        $this->db->from($this->tableName)
            ->where("game_platform_id", $game_platform_id);

        foreach($where as $key => $value) {
            $this->db->where($key, $value);
        }
        $query = $this->db->get();

        return $this->getMultipleRow($query);
    }
}
