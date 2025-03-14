<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Dispatch_withdrawal_definition
 *
 */
class Hedging_total_detail_player extends BaseModel {
    protected $tableName = 'hedging_total_detail_player';


    public function __construct() {
        parent::__construct();
    }


    /**
     * Add a record
     *
     * @param array $params the fields of the table,"dispatch_withdrawal_definition".
     * @return void
     */
    public function add($params) {

        $nowForMysql = $this->utils->getNowForMysql();
        $data['created_at'] = $nowForMysql;
        $data['updated_at'] = $nowForMysql;
        $data = array_merge($data, $params);
        return $this->insertRow($data);
    } // EOF add


    public function addByTableAndPlayer($params ){
        $return = [];
        $return['result'] = null;

        $table_id = $params['table_id'];
        $player_id = $params['player_id'];
        $select = 'id';
        $where = <<<EOF
table_id = "$table_id" and player_id = "$player_id"
EOF;
        $rows = $this->getDetailListByQuery($select, $where);
        $return['is_added'] = false;
        if( empty($rows) ){
            $return['is_added'] = true;
            $return['result'] = $this->add($params);
        }else{
            $id = $rows[0]['id'];
            $data = $params;
            $return['result'] = $this->update($id, $data );
        }
        return $return;
    } // EOF addByTableAndPlayer

    /**
     * Update record by id
     *
     * @param integer $id
     * @param array $data The fields for update.
     * @return boolean|integer The affected_rows.
     */
    public function update($id, $data = array() ) {
        $nowForMysql = $this->utils->getNowForMysql();
        $data['updated_at'] = $nowForMysql;
        return $this->updateRow($id, $data);
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
     * Get the rows for withdrawal_risk_api_module::processPreChecker()
     *
     * @param boolean $getEnabledOnly filter the inactived rows.
     * @param string $order_by_field The field name.
     * @param string $order_by order asc/desc.
     * @return array The rows.
     */
    public function getDetailList($getEnabledOnly = true, $order_by_field='dispatch_order', $order_by ='asc'){
        $this->db->select('*')
                ->from($this->tableName);
        if($getEnabledOnly){
            $this->db->where('status', BaseModel::DB_TRUE);
        }

        $this->db->order_by($order_by_field, $order_by);
        $result = $this->runMultipleRowArray();

        return $result;
    }// EOF getDetailList

    /**
     * query data by select and where
     *
     * @param array|string $select
     * @param array|string $where
     * @return array The result rows
     */
    public function getDetailListByQuery($select, $where = null){
		$this->db->select($select);

		if ($where)
			$this->db->where($where);

        $this->db->from($this->tableName);
        $result = $this->runMultipleRowArray();

        return $result;
	} // EOF getDetailListByQuery


}

///END OF FILE////////