<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Cispatch_withcashback_to_bet_list_mappingdrawal_definition
 *
 */
class Cashback_to_bet_list_mapping extends BaseModel {
    protected $tableName = 'cashback_to_bet_list_mapping';
    protected $column_name_list = [];

    public function __construct() {
        parent::__construct();

        $this->column_name_list[] = 'cashback_table';
        $this->column_name_list[] = 'cashback_id';
        $this->column_name_list[] = 'bet_source_table';
        $this->column_name_list[] = 'bet_source_id';
        $this->column_name_list[] = 'created_at';
        $this->column_name_list[] = 'updated_at';
        $this->column_name_list[] = 'player_id';

    }

    /**
     * Reserve the data[n] by column_name_list.
     *
     * @param array $data The key-value array, as field name and the value.
     * @return array The array, That's elements owns the key exists in the column_name_list.
     */
    public function reserve_column_name_list($data){
        $_data = [];
        foreach( array_values($this->column_name_list) as $_column_name ){
            if( isset($data[$_column_name]) ){
                $_data[$_column_name] = $data[$_column_name];
            }
        }
        return $_data;
    }// EOF reserve_column_name_list

    /**
     * Add a record
     *
     * @param array $params the fields of the table,"dispatch_withdrawal_definition".
     * @return void
     */
    public function add($params) {
        $return_rlt = null;
        // $nowForMysql = $this->utils->getNowForMysql();
        // $data['created_at'] = $nowForMysql;
        // $data['updated_at'] = $nowForMysql;
        $_data = $this->reserve_column_name_list($params);
        if( ! empty($_data) ){
            $return_rlt = $this->insertRow($_data);
        }

        return $return_rlt;
    } // EOF add


    /**
     * Update record by id
     *
     * @param integer $id
     * @param array $data The fields for update.
     * @return boolean|integer The affected_rows.
     */
    public function update($id, $data = array() ) {
        $return_rlt = null;
        // $nowForMysql = $this->utils->getNowForMysql();
        // $data['updated_at'] = $nowForMysql;
        $_data = $this->reserve_column_name_list($data);
        if( ! empty($_data) ){
            $return_rlt = $this->updateRow($id, $_data);
        }
        return $return_rlt;
    }// EOF update

    /**
     * Delete a record by id(P.K.)
     *
     * @param integer $id The id field.
     * @return boolean Return true means delete the record completed else false means failed.
     */
    public function delete($id){
        $this->db->where('id', $id);
        return $this->runRealDelete($this->tableName);
    }// EOF delete

    /**
     * Get a record by id(P.K.)
     *
     * @param integer $id The id field.
     * @return array The field-value of the record.
     */
    public function getDetailById($id) {
        $this->db->select('*')
                ->from($this->tableName)
                ->where('id', $id);

        $result = $this->runOneRowArray();

        return $result;
    }// EOF getDetailById

    /**
     * The Callback for default script in the param, "isExistsCallBack" of the function,"syncToDataAfterSyncCashbackDaily".
     *
     * @param array $where_clause_list  The key-value array, as the field name and the value in where clause.
     * @param array $exists_id_list The  caller will get the exists id list by the param.
     * @return bool If its true, the data exists in the mapping table. If its false, the data Not exists in the mapping table.
     */
    public function syncToDataAfterSyncCashbackDaily_isExistsCallBack($where_clause_list = [], &$exists_id_list = []){
        $isExists = null;
        $this->db->select('*');
        $this->db->from($this->tableName);

        if( ! empty($where_clause_list) ){
            foreach($where_clause_list as $_where_field_name => $_where_value){
                if( in_array($_where_field_name, $this->column_name_list) ){
                    if( is_string($_where_value) ){
                        $this->db->where($_where_field_name, $_where_value);
                    }
                }

            }
        }


        $rows = $this->runMultipleRowArray();
        if( ! empty($rows) ){
            foreach($rows as $_row){
                $exists_id_list[] = $_row['id']; // assign in the param, $is_exists_id_list
            }

            $isExists = true;
        }else{
            $isExists = false;
        }
        return $isExists;
    }

    /**
     * Sync ONE data into cashback_to_bet_list_mapping, after SyncCashbackDaily().
     *
     * @param array $data The $data should be contains the followings,
     * - $data[cashback_table] string The cashback report table name, it also may be the suffixed data table of the recalculate cashback report.
     * - $data[cashback_id] instger The P.K. of the cashback_table.
     * - $data[bet_source_table] string The bet source table, ex: game_logs, total_cashback_player_game_daily,...
     * - $data[bet_source_id] string The bet source table, ex: game_logs, total_cashback_player_game_daily,...
     *
     * @param callable|null $isExistsCallBack
     * @return void
     */
    public function syncToDataAfterSyncCashbackDaily($data, callable $isExistsCallBack = null){

        if( empty($isExistsCallBack) ){
            $_this = $this;
            $isExistsCallBack = function ($where_clause_list, &$exists_id_list) use ($_this){
                return call_user_func_array([$_this, 'syncToDataAfterSyncCashbackDaily_isExistsCallBack'], [$where_clause_list, &$exists_id_list]);
                // "$this->syncToDataAfterSyncCashbackDaily_isExistsCallBack($where_clause_list)" in callable.
            }; // EOF $isExistsCallBack
        }
        $_where_clause_list = [];
        $_where_clause_list['cashback_id'] = $data['cashback_id'];
        $_where_clause_list['cashback_table'] = $data['cashback_table'];
        $_where_clause_list['bet_source_table'] = $data['bet_source_table'];
        $_where_clause_list['bet_source_id'] = $data['bet_source_id'];


        $exists_id_list = [];
        $_isExists = $isExistsCallBack($_where_clause_list, $exists_id_list);


        $_params = [];
        $_params['cashback_table'] = $data['cashback_table'];
        $_params['cashback_id'] = $data['cashback_id'];
        $_params['bet_source_table'] = $data['bet_source_table'];
        $_params['bet_source_id'] = $data['bet_source_id'];
        // if( isset($data['is_pay']) ){
        //     $_params['is_pay'] = $data['is_pay'];
        // }
        if( isset($data['player_id']) ){ // cashback_report.player_id
            $_params['player_id'] = $data['player_id'];
        }
$this->utils->debug_log('OGP-27272.189._params', $_params, 'exists_id_list:', $exists_id_list);
        $affected_id = false;
        if( $_isExists ){
            // should be update
            if( count($exists_id_list ) > 1 ){
                // one more data need to update, its not expected.
                foreach($exists_id_list  as $exists_id){
                    // $_rlt = $this->update($exists_id, $_params);
                }
                $this->utils->debug_log('exists_id_list.count', count($exists_id_list), '_where_clause_list:', $_where_clause_list);
            }else{

                // should be one data.
                $exists_id = $exists_id_list[0];
                $_rlt = $this->update($exists_id, $_params);
                if($_rlt ){
                    // the case, "on changed" had includes.
                    $affected_id = $exists_id;
                }else{
                    $affected_id = $exists_id;
                }
            }

        }else{
            // should be insert
            $_rlt = $this->add($_params);
            if($_rlt){
                $affected_id = $this->db->insert_id();
            }
        }
        return $affected_id;
    } // EOF syncToDataAfterSyncCashbackDaily

    /**
     * Sync the data with bet_source_id_list into cashback_to_bet_list_mapping, after SyncCashbackDaily().
     *
     * @param array $data The $data should be contains the followings,
     * - $data[cashback_table] string The cashback report table name, it also may be the suffixed data table of the recalculate cashback report.
     * - $data[cashback_id] instger The P.K. of the cashback_table.
     * - $data[bet_source_table] string The bet source table, ex: game_logs, total_cashback_player_game_daily,...
     * - $data[bet_source_id_list] string The imploded P.K. list of the bet source table, ex: "1,12,13,45,46,37,9,23"<div class=""></div>
     * @return array $_rlt_list The result information, it contains the followings,
     * - $_rlt_list[bool] bool So far, its always be true.
     * - $_rlt_list[bet_source_id_list_count] integer The counter of bet_source_id.
     * - $_rlt_list[result_list] The key-value array, its mapping to bet_source_id and the return of syncToDataAfterSyncCashbackDaily().
     *
     */
    public function syncToDataWithBetSourceIdListAfterSyncCashbackDaily($data){
        $_rlt_list = [];
        $_result_list = [];

        $_bet_source_id_list = [];
        if( ! empty($data['bet_source_id_list']) ){
            $bet_source_id_list_str = $data['bet_source_id_list'];
            $_bet_source_id_list = explode(',', $bet_source_id_list_str);
        }

        $_rlt_list['bet_source_id_list_count'] = 0;
        if( ! empty($_bet_source_id_list) ){
            $_rlt_list['bet_source_id_list_count'] = count($_bet_source_id_list);
            $_params = $data;
            unset($_params['bet_source_id_list']);
            foreach($_bet_source_id_list as $bet_source_id){
                $_params['bet_source_id'] = $bet_source_id;
                $_result_list[$bet_source_id] = $this->syncToDataAfterSyncCashbackDaily($_params);
            }
        }
        $_rlt_list['result_list'] = $_result_list;
        $_rlt_list['bool'] = true;
        return $_rlt_list;
    } // EOF syncToDataWithBetSourceIdListAfterSyncCashbackDaily

}

///END OF FILE////////