<?php
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Original Seamless Wallet Transactions Model
 *
 * This class handles database interactions and logic for managing seamless wallet transactions.
 * It extends the BaseModel to inherit shared functionality such as query building and ORM features.
 *
 * Typical usage:
 * - Interfacing with the seamless wallet transactions table.
 * - Performing CRUD operations related to wallet transactions.
 * - Defining relationships and additional query scopes.
 *
 * @author Melvin (melvin.php.ph)
 */
class Original_seamless_wallet_transactions extends BaseModel
{
    public $CI;

    public function __construct()
    {
        parent::__construct();
    }

    public function getPlayerSingleTransactionByType($table_name, $transaction_type, $player_id, $game_id, $round_id)
    {
        $this->db->from($table_name)
        ->where('transaction_type', $transaction_type)
        ->where('player_id', $player_id)
        ->where('game_id', $game_id)
        ->where('round_id', $round_id);

        return $this->runOneRowArray();
    }

    public function querySingleTransactionCustom($table_name, $fields = [], $selectedColumns = [], 
        $order_by = ['field_name' => '', 'is_desc' => false]
    ) {
        if (!empty($selectedColumns) && is_array($selectedColumns)) {
            $columns = implode(",", $selectedColumns);
            $this->db->select($columns);
        }

        $this->db->from($table_name)->where($fields);

        if (!empty($order_by['field_name'])) {
            $sort = isset($order_by['is_desc']) && $order_by['is_desc'] ? 'DESC' : 'ASC';
            $this->db->order_by($order_by['field_name'], $sort);
        }

        return $this->runOneRowArray();
    }

    public function queryPlayerTransactions($table_name, $player_id, $game_id, $round_id)
    {
        $this->db->from($table_name)
        ->where('player_id', $player_id)
        ->where('game_id', $game_id)
        ->where('round_id', $round_id);

        return $this->runMultipleRowArray();
    }

    public function queryPlayerTransactionsCustom($table_name, $fields = [], $selected_columns = [], 
        $order_by = ['field_name' => '', 'is_desc' => false])
    {
        if (!empty($selected_columns) && is_array($selected_columns)) {
            $columns = implode(',', $selected_columns);
            $this->db->select($columns);
        }

        $this->db->from($table_name)->where($fields);

        if (!empty($order_by['field_name'])) {
            $sort = isset($order_by['is_desc']) && $order_by['is_desc'] ? 'DESC' : 'ASC';
            $this->db->order_by($order_by['field_name'], $sort);
        }

        return $this->runMultipleRowArray();
    }

    public function isTransactionExist($table_name, $external_unique_id)
    {
        $this->db->from($table_name)->where('external_unique_id', $external_unique_id);
        return $this->runExistsResult();
    }

    public function isTransactionExistCustom($table_name, $fields = [])
    {
        $this->db->from($table_name)->where($fields);
        return $this->runExistsResult();
    }

    public function insertTransactionData($table_name, $data = [], $db = null)
    {
        return $this->insertData($table_name, $data, $db);
    }

    public function updateTransactionDataWithResult($table_name, $data = [], $field_id, $id, $db = null)
    {
        $this->db->where($field_id, $id)->set($data);
        return $this->runAnyUpdateWithResult($table_name, $db);
    }

    public function updateTransactionDataWithoutResult($table_name, $data = [], $field_id, $id, $db = null)
    {
        $this->db->where($field_id, $id)->set($data);
        return $this->runAnyUpdateWithoutResult($table_name, $db);
    }

    public function updateTransactionDataWithResultCustom($table_name, $fields = [], $data = [], $db = null)
    {
        $this->db->where($fields)->set($data);
        return $this->runAnyUpdateWithResult($table_name, $db);
    }

    public function updateTransactionDataWithoutResultCustom($table_name, $fields = [], $data = [], $db = null)
    {
        $this->db->where($fields)->set($data);
        return $this->runAnyUpdateWithoutResult($table_name, $db);
    }

    public function insertOrUpdateTransactionData($table_name, $query_type, $data = [], $field_id = null, $id = null, $update_with_result = false, $db = null)
    {
        if (!empty($data) && is_array($data)) {
            switch ($query_type) {
                case 'insert':
                    $result = $this->insertTransactionData($table_name, $data, $db);
                    break;
                case 'update':
                    if ($update_with_result) {
                        $result = $this->updateTransactionDataWithResult($table_name, $data, $field_id, $id, $db);
                    } else {
                        $result = $this->updateTransactionDataWithoutResult($table_name, $data, $field_id, $id, $db);
                    }
                    break;
                default:
                    $result = [];
                    break;
            }
        } else {
            $result = [];
        }

        return $result;
    }

    public function saveTransactionData($table_name, $query_type, $data = [], $where = [], $update_with_result = false, $db = null)
    {
        if (!empty($data) && is_array($data)) {
            switch ($query_type) {
                case 'insert':
                    $result = $this->insertTransactionData($table_name, $data, $db);
                    break;
                case 'update':
                    if ($update_with_result) {
                        $result = $this->updateTransactionDataWithResultCustom($table_name, $where, $data, $db);
                    } else {
                        $result = $this->updateTransactionDataWithoutResultCustom($table_name, $where, $data, $db);
                    }
                    break;
                default:
                    $result = [];
                    break;
            }
        } else {
            $result = [];
        }

        return $result;
    }

    public function updateOrInsertOriginalGameLogs($table_name, $data = [], $query_type)
    {
        $this->CI->load->model(['original_game_logs_model']);
        $data_count = 0;

        if (!empty($data)) {
            foreach ($data as $record) {
                if ($query_type == 'update') {
                    $record['updated_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($table_name, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($table_name, $record);
                }
                $data_count++;
                unset($record);
            }
        }

        return $data_count;
    }

    public function getLastStatusOfCommonData($table_name, $fields = [], $selected_column_status = "sbe_status"){
        $query = $this->db->select_max("id")
                    ->from($table_name)
                    ->where($fields)
                    ->get();

        $maxID = $this->getOneRowOneField($query,"id");
        $query2 = $this->db->select($selected_column_status)
                    ->from($table_name)
                    ->where("id",$maxID)
                    ->get();

        $status = $this->getOneRowOneField($query2, $selected_column_status);
        return array($maxID, $status);
    }

    public function insertIgnoreTransactionData($table_name, $data = [], $db = null)
    {
        return $this->insertIgnoreData($table_name, $data, $db);
    }

    public function querySingleTransactionCustomWithdb($table_name, $fields = [], $selectedColumns = [], $db = null)
    {
        if(empty($db) || !is_object($db)){
            $db=$this->db;
        }

        if(!empty($selectedColumns) && is_array($selectedColumns)){
            $columns = implode(",", $selectedColumns);
            $db->select($columns);
        }
        $db->from($table_name)->where($fields);
        return $this->runOneRowArray($db);
    }

    public function getSpecificField($table_name, $field = '', $where = []) {
        $this->db->from($table_name)->where($where);
        return $this->runOneRowOneField($field);
    }
}