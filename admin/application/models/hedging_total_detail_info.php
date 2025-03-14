<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Dispatch_withdrawal_definition
 *
 */
class Hedging_total_detail_info extends BaseModel {
    public $tableName = 'hedging_total_detail_info';

    public $md5sumFieldName = 'md5sum';

    public $exceptFieldsWhileImport = ['md5sum','created_at','updated_at'];

    public function __construct() {
        parent::__construct();
    }


    public function columnsMapping($mode = 'xls2table'){
		$columns = [];
		switch($mode){
            case 'table2xls':
			case 'xls2table':{
				$columns['A'] = 'table_id'; // # 1
				$columns['B'] = 'contnet_id'; // # 2
				$columns['C'] = 'members'; // # 3
				$columns['D'] = 'banker'; // # 4
				$columns['E'] = 'player'; // # 5
				$columns['F'] = 'dragon'; // # 6
				$columns['G'] = 'tiger'; // # 7
				$columns['H'] = 'big'; // # 8
				$columns['I'] = 'small'; // # 9

				$columns['J'] = 'sic_bo_odd'; // # 10 // Sic Bo  SicboOdd
				$columns['K'] = 'sic_bo_even'; // # 11 // Sic Bo

				$columns['L'] = 'red'; // # 12
				$columns['M'] = 'black'; // # 13

				$columns['N'] = 'roulette_odd'; // # 14 // RouletteOdd
				$columns['O'] = 'roulette_even'; // # 15

				$columns['P'] = 'hedge_difference';  // # 16
				$columns['Q'] = 'hedge_index'; // # 17   Hedging Index
                $columns['R'] = 'hedge_spicious'; // # 18  Spicious of Hedging

                if($mode == 'table2xls') { // swap key and value in array
                    $flip = array_flip($columns);
                    $columns = $flip;
                }
				break;
			}
        }
		return $columns;
	} // EOF columnsMapping


    /**
	 * get md5sum() by row
	 *
	 * @param array $row
	 * @return string $md5
	 */
	public function md5sum_row($row, $exceptColumnNameList = []){
		$md5sum = '';
        $exceptColumnName = 'md5sum';
        array_push($exceptColumnNameList, $exceptColumnName);

		if( ! empty($row) ){
			$tmp = '';
			foreach($row as $column => $value){
                if( ! in_array($column, $exceptColumnNameList) ){
                    $tmp .= $value;
                }
			}
			$md5sum = md5($tmp);
		}

		return $md5sum;
	} // EOF md5sum_row

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

    /**
     * Add the row by md5sum.
     *
     * @param array $params The row data, key-value array.
     * @param string $md5sum The md5sum of the row.
     * @return array The result detail array.
     */
    public function addByMd5sum($params, $md5sum = ''){

        // $return defaults
        $return = [];
        $return['rowInTable'] = null;
        $return['result'] = null;
        $return['result_msg'] = null;
        $return['md5sum'] = null;

        $isExists = false;
        if( empty($md5sum) ){
            $md5sum = $this->md5sum_row($params, ['created_at','updated_at']);
        }
        $rowInTable = $this->getDetailById($md5sum, 'md5sum');

        if( ! empty($rowInTable) ){
            $isExists = true;
            $return['rowInTable'] = $rowInTable;
        }

        $return['md5sum'] = $md5sum;
        if( ! $isExists ){
            $params['md5sum'] = $md5sum;
            $return['result'] = $this->add($params);
            $return['result_msg'] = '';
        }else{
            $return['result'] = null;
            $return['result_msg'] = 'isExists = true';
        }

        $return['params'] = $params;
        return $return;
    } // EOF addByMd5sum

    /**
     * Parse the field, members for get the username of players.
     * https://regex101.com/r/4Apo8b/1
     *
     * @param string $theMembers
     * @return void
     */
    public function parseMembersField2player($theMembers = '', $prefix_for_username = 'ocn', $doCheckUsernameExist = true ){

        $this->load->model(array('player'));

        $matchPlayerList = [];
        $re = "/$prefix_for_username(?P<username>[a-z0-9]{6,99})$/m";
        // $str = '***
        // x89xiaobenhu
        // ***
        // x89xiao23nhu
        // ***
        // x89xiaox89hu
        // ***
        // 總計(CNY):';

        preg_match_all($re, $theMembers, $matches, PREG_SET_ORDER, 0);

        $matchPlayerList = [];
        if( ! empty( $matches ) ){

            $usernameList = [];
            foreach($matches as $matche){
                $usernameList[] = $matche['username'];
            }

            // unique the $matchPlayerList
            $usernameList = array_unique($usernameList);
            if($doCheckUsernameExist){
                foreach( $usernameList as $username){
                    // $username = 'test002';
                    $playerRow = $this->player->checkUsernameExist($username);
                    if( ! empty($playerRow) ){
                        $matchPlayerList[$playerRow['playerId']] = $username;
                    }
                }
            }else{
                $index = 0;
                foreach( $usernameList as $username){
                    $matchPlayerList['index_'.$index] = $username;
                    $index++;
                }
            }


        }
        return $matchPlayerList;
    } // EOF parseMembersField2player

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
     * @param string $idFieldName The P.K. field name. The default is "id".
     * @return array The field-value of the record.
     */
    public function getDetailById($id, $idFieldName = 'id') {
        $this->db->select('*')
                ->from($this->tableName)
                ->where($idFieldName, $id);

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

}

///END OF FILE////////