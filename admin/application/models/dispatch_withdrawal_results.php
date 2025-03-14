<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';
require_once dirname(__FILE__) . '/modules/monthly_partition_module.php';

/**
 * Dispatch_withdrawal_results
 *
 */
class Dispatch_withdrawal_results extends BaseModel {
	protected $base_on_tableName = 'dispatch_withdrawal_results';
    protected $tableName = 'dispatch_withdrawal_results';
    protected $enabled_dispatch_withdrawal_results_monthly_table = false;

    use monthly_partition_module;

	CONST RESULT_DW_STATUS_CODE_DISALLOW_DWSTATUS = 0x101; // 257
	CONST RESULT_DW_STATUS_CODE_DWSTATUS_HAS_PROCESSED = 0x102; // 258
	CONST RESULT_DW_STATUS_CODE_DWSTATUS_SKIP_BY_CONFIG = 0x103; // 259

	public function __construct() {

        parent::__construct();

        $this->enabled_dispatch_withdrawal_results_monthly_table = $this->CI->config->item('enabled_dispatch_withdrawal_results_monthly_table');

        if($this->enabled_dispatch_withdrawal_results_monthly_table){
            $dateTimeStr = 'now';
            // When update the data
            // It still Needs to check the table, "dispatch_withdrawal_result".
            // for legacy data update.
        }else{
            $dateTimeStr = '';
        }
        $_this = $this;
        $precreateNext = true;
        $this->tableName = $this->getTablenameWithMonthlyPartitionByDate($this->base_on_tableName, $dateTimeStr, function($_tableName) use( &$_this ) {
            $_this->utils->debug_log('will _create_table4monthly_partition._tableName:', $_tableName);
            return $_this->_create_table4monthly_partition($_tableName);
        }, $precreateNext );

        // $this->tableName = $this->getTablenameWithMonthlyPartitionByDate($tableName)

        // $this->tableName = $this->getDispatchWithdrawalResultsMonthlyTable();
	}

    /**
     * create table for the function createTableWithMonthlyPartitionByDate
     *
     * @param string $tableName The table name in monthly partition.
     * @return boolean The table exists boolean.
     */
    public function _create_table4monthly_partition($tableName){
        if (!$this->utils->table_really_exists($tableName)) {
			try{
                $this->CI->load->dbforge();

                $fields = array(
                    'id' => array(
                        'type' => 'INT',
                        'unsigned' => TRUE,
                        'auto_increment' => TRUE,
                    ),
                    "wallet_account_id" => array(  // F.K. walletaccount.walletAccountId
                        "type" => "BIGINT",
                        "null" => false
                    ),
                    "definition_id" => array(  // F.K. dispatch_withdrawal_definition.id
                        "type" => "BIGINT",
                        "null" => false
                    ),
                    "definition_results" => array(
                        "type" => "JSON",
                        "null" => true
                    ),
                    "result_dw_status" => array(
                        "type" => "varchar",
                        "constraint" => "255",
                        "null" => true
                    ),
                    "dispatch_order" => array(
                        'type' => 'INT',
                        "null" => true
                    ),

                    "definition2dw_status" => array(
                        "type" => "varchar",
                        "constraint" => "255",
                        "null" => true
                    ),
                    "after_status" => array(
                        "type" => "varchar",
                        "constraint" => "255",
                        "null" => true
                    ),
                    'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                        'null' => false,
                    ),
                    'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                        'null' => false,
                    ),
                );

                $this->dbforge->add_field($fields);
                $this->dbforge->add_key("id",true); // for P.K.
                $this->dbforge->create_table($tableName);




                $this->utils->debug_log('will add index tableName:', $tableName
                                        , 'getActiveTargetDB:', $this->utils->getActiveTargetDB()
                                    );
                $this->load->model('player_model'); # Any model class will do
                # add Index
                $this->player_model->addIndex($tableName,"idx_dispatch_withdrawal_results_definition_id","definition_id");
                $this->player_model->addIndex($tableName,"idx_dispatch_withdrawal_results_wallet_account_id","wallet_account_id");
                $this->utils->debug_log('had added index last_query: ', $this->player_model->db->last_query(), 'tableName:', $tableName);

			}catch(Exception $e){
				$this->utils->error_log('create table failed: '.$tableName, $e);
			}
		} // EOF if (!$this->table_really_exists($tableName)) {...
        return !!$this->utils->table_really_exists($tableName);
    }

	/**
	 * Add a record
	 *
	 * @param array $params the fields of the table,"dispatch_withdrawal_results".
	 * @return void
	 */
	public function add($params) {
		$data = [];
		$data = array_merge($data, $params);

		return $this->insertRow($data);
	} // EOF add

	/**
	 * Update record by id
	 *
	 * @param integer $id
	 * @param array $data The fields for update.
	 * @return boolean|integer The affected_rows.
	 */
	public function update($id, $data = array() ) {

		return $this->updateRow($id, $data);
	} // EOF update


	public function updateByDefinitionIdAndWalletAccountId($walletAccountId, $definitionId, $data = array()){
        $results = [];
        if( ! $this->enabled_dispatch_withdrawal_results_monthly_table ){
            // orignal
            $_results = [];
            $this->db->select('id')
                ->from($this->tableName)
                ->where('wallet_account_id', $walletAccountId);
            $this->db->where('definition_id', $definitionId);
            $rowList = $this->runMultipleRowArray();

            if( ! empty($rowList) ){
                foreach($rowList as $indexNumber => $row){
                    $id = $row['id'];
                    $_results[] = $this->update($id, $data);
                }
            }
            $results[$this->base_on_tableName] = $_results;
        }else{
            /// in monthly table
            // for non-monthly table
            $_tableName = $this->combineMonthlyTableName($this->base_on_tableName, '');
            $results[$_tableName] = $this->updateByDefinitionIdAndWalletAccountIdWithTableName($walletAccountId, $definitionId, $data, $_tableName);
            // for current month table
            $_tableName = $this->combineMonthlyTableName($this->base_on_tableName, 'now');
            $results[$_tableName] = $this->updateByDefinitionIdAndWalletAccountIdWithTableName($walletAccountId, $definitionId, $data, $_tableName);
            // for previous month table
            $_tableName = $this->combineMonthlyTableName($this->base_on_tableName, 'first day of previous month');
            $results[$_tableName] = $this->updateByDefinitionIdAndWalletAccountIdWithTableName($walletAccountId, $definitionId, $data, $_tableName);
        }
        $this->utils->debug_log('OGP-30060.185.updateByDefinitionIdAndWalletAccountId.results:', $results);
		return $results;
	} // EOF updateByDefinitionIdAndWalletAccountId
    //
    public function updateByDefinitionIdAndWalletAccountIdWithTableName($walletAccountId, $definitionId, $data = array(), $tableName = 'dispatch_withdrawal_results'){
        $results = [];
        if ( $this->utils->table_really_exists($tableName) ) {
            $this->db->select('id')
            ->from($tableName)
            ->where('wallet_account_id', $walletAccountId)
            ->where('definition_id', $definitionId);
            $rowList = $this->runMultipleRowArray();
            if( ! empty($rowList) ){
                foreach($rowList as $indexNumber => $row){
                    $id = $row['id'];
                    $fieldId = $this->getIdField();
                    $this->updateData($fieldId, $id, $tableName, $data);
                    $results[] = $this->db->affected_rows();
                }
            }
        } // EOF if ( $this->utils->table_really_exists($tableName) ) {...

        return $results;
    } // EOF updateByDefinitionIdAndWalletAccountIdWithTableName

	/**
	 * Delete a record by id(P.K.)
	 *
	 * @param integer $id The id field.
	 * @return boolean If true means delete the record completed else false means failed.
	 */
	public function delete($id){
		$this->db->where('id', $id);
		return $this->runRealDelete($this->tableName);
	} // EOF delete

	/**
	 * Get the rows by walletAccountId
	 *
	 * @param integer $conditions_id The field,"walletaccount.walletAccountId".
	 * @param boolean $isExtractFinallyResult Query with JSON_EXTRACT(definition_results).
	 * @param string $sortByFinallyResult Trigger to sort by JSON_EXTRACT(definition_results) and updated_at field.
	 * @param array $results_json_extract The json_extract() for definition_results field in the array.
	 * The format as following,
	 * $results_json_extract['finallyResult'] = '$.finallyResult';
	 * The key as field name and the value as the json path of definition_results.
	 * @return array The rows.
	 */
    public function getDetailListByWalletAccountId($walletAccountId, $isExtractFinallyResult = false, $sortByFinallyResult = '', $results_json_extract = null){
        $result = [];
        if( $this->enabled_dispatch_withdrawal_results_monthly_table){
            $_tableName = $this->combineMonthlyTableName($this->base_on_tableName, '');
            $_result = $this->_getDetailListByWalletAccountIdWithTableName($walletAccountId, $isExtractFinallyResult, $sortByFinallyResult, $results_json_extract, $_tableName);
            $result = array_merge($result, $_result);
            $_tableName = $this->combineMonthlyTableName($this->base_on_tableName, 'now');
            $_result = $this->_getDetailListByWalletAccountIdWithTableName($walletAccountId, $isExtractFinallyResult, $sortByFinallyResult, $results_json_extract, $_tableName);
            $result = array_merge($result, $_result);
            $_tableName = $this->combineMonthlyTableName($this->base_on_tableName, 'first day of previous month');
            $_result = $this->_getDetailListByWalletAccountIdWithTableName($walletAccountId, $isExtractFinallyResult, $sortByFinallyResult, $results_json_extract, $_tableName);
            $result = array_merge($result, $_result);
        }else{
            $result = $this->_getDetailListByWalletAccountId($walletAccountId, $isExtractFinallyResult, $sortByFinallyResult, $results_json_extract);
        }
        return $result;
    }
	public function _getDetailListByWalletAccountId($walletAccountId, $isExtractFinallyResult = false, $sortByFinallyResult = '', $results_json_extract = null){

		$this->db->select( [ 'id'
			, 'wallet_account_id'
			, 'definition_id'
			// , 'definition_results'// The field might cause the Fatal Error, "Allowed memory size exhausted".
			, " IFNULL( JSON_UNQUOTE(JSON_EXTRACT(definition_results, '$.finallyResult')), NULL ) AS finallyResult "
			, " IFNULL( JSON_UNQUOTE(JSON_EXTRACT(definition_results, '$.conditions_name')), NULL ) AS conditions_name "
			, " IFNULL( JSON_UNQUOTE(JSON_EXTRACT(definition_results, '$.conditions_id')), NULL ) AS conditions_id "
			, 'result_dw_status'
			, 'definition2dw_status'
			, 'after_status'
			, 'created_at'
			, 'updated_at' ] )
			->from($this->tableName)
			->where('wallet_account_id', $walletAccountId);

		if($isExtractFinallyResult){
			if( is_null($results_json_extract) ){
				$results_json_extract['finallyResult'] = '$.finallyResult';
				// like as $this->db->select('JSON_EXTRACT(definition_results, "$.finallyResult") AS finallyResult', FALSE);
			}
			if( !empty($results_json_extract) ){
				foreach($results_json_extract as $_field_name => $_json_path){
					$this->db->select('JSON_EXTRACT(definition_results, "'. $_json_path. '") AS '. $_field_name, FALSE);
				}
			}
		}

		if( ! empty($sortByFinallyResult) ){
			$this->db->order_by('CONCAT(JSON_EXTRACT(definition_results, "$.finallyResult"), updated_at) ', $sortByFinallyResult, FALSE);
		}

		$result = $this->runMultipleRowArray();

		if( ! empty($result) ){ // generate the definition_results data for the "Risk Check Status" field of the withdraw list.
			foreach( $result as $indexNumber => $row ){
				$_definition_results = [];
				$_definition_results['finallyResult'] = $row['finallyResult'];
				$_definition_results['conditions_name'] = $row['conditions_name'];
				$_definition_results['conditions_id'] = $row['conditions_id'];
				$result[$indexNumber]['definition_results'] = json_encode($_definition_results) ;
			}
		}
		return $result;
	}// EOF _getDetailListByWalletAccountId
    //
    public function _getDetailListByWalletAccountIdWithTableName($walletAccountId, $isExtractFinallyResult = false, $sortByFinallyResult = '', $results_json_extract = null, $tableName = 'dispatch_withdrawal_results'){

        if ( ! $this->utils->table_really_exists($tableName) ) {
            $result = [];
            return $result;
        }

        $_parser = $this->parserMonthlyTableName($tableName);
		$this->db->select( [ 'wallet_account_id'
			, 'definition_id'
			// , 'definition_results'// The field might cause the Fatal Error, "Allowed memory size exhausted".
			, " IFNULL( JSON_UNQUOTE(JSON_EXTRACT(definition_results, '$.finallyResult')), NULL ) AS finallyResult "
			, " IFNULL( JSON_UNQUOTE(JSON_EXTRACT(definition_results, '$.conditions_name')), NULL ) AS conditions_name "
			, " IFNULL( JSON_UNQUOTE(JSON_EXTRACT(definition_results, '$.conditions_id')), NULL ) AS conditions_id "
			, 'result_dw_status'
			, 'definition2dw_status'
			, 'after_status'
			, 'created_at'
			, 'updated_at' ] )
			->from($tableName)
			->where('wallet_account_id', $walletAccountId);

        if( ! empty($_parser['suffix']) ){
            $suffix = $_parser['suffix'];
            // $this->db->select("'$suffix' as Ym", null, false);
            $this->db->select("CONCAT('$suffix', id ) id");
        }else{
            // $this->db->select("'000000' as Ym", null, false);
            // $this->db->select("LPAD( id, 6, '0') id");
            $this->db->select(" CONCAT('000000', id ) id ");
        }


		if($isExtractFinallyResult){
			if( is_null($results_json_extract) ){
				$results_json_extract['finallyResult'] = '$.finallyResult';
				// like as $this->db->select('JSON_EXTRACT(definition_results, "$.finallyResult") AS finallyResult', FALSE);
			}
			if( !empty($results_json_extract) ){
				foreach($results_json_extract as $_field_name => $_json_path){
					$this->db->select('JSON_EXTRACT(definition_results, "'. $_json_path. '") AS '. $_field_name, FALSE);
				}
			}
		}

		if( ! empty($sortByFinallyResult) ){
			$this->db->order_by('CONCAT(JSON_EXTRACT(definition_results, "$.finallyResult"), updated_at) ', $sortByFinallyResult, FALSE);
		}

		$result = $this->runMultipleRowArray();
        $this->utils->debug_log('OGP-30060.346._getDetailListByWalletAccountIdWithTableName.last_query:', $this->db->last_query());

		if( ! empty($result) ){ // generate the definition_results data for the "Risk Check Status" field of the withdraw list.
			foreach( $result as $indexNumber => $row ){
				$_definition_results = [];
				$_definition_results['finallyResult'] = $row['finallyResult'];
				$_definition_results['conditions_name'] = $row['conditions_name'];
				$_definition_results['conditions_id'] = $row['conditions_id'];
				$result[$indexNumber]['definition_results'] = json_encode($_definition_results) ;
			}
		}
		return $result;
	}// EOF getDetailListByWalletAccountId

	/**
	 * Get the records of walletaccount by transCode
	 *
	 * @param string $transCode The field,"transactionCode" of walletaccount.
	 * @param null|point $walletAccountId To get the field,"walletAccountId" by transactionCode in walletaccount.
	 * @param null|point $dwDateTime To get the field,"dwDateTime" by transactionCode in walletaccount.
	 * @return array The records.
	 */
	public function isExistsInByTransCode($transCode, &$walletAccountId = null, &$dwDateTime = null){
		$this->load->model(array('Wallet_model'));
		$walletAccountDeatil = $this->wallet_model->getWalletAccountByTransactionCode($transCode);
		$walletAccountId = $walletAccountDeatil['walletAccountId'];
		$dwDateTime = $walletAccountDeatil['dwDateTime']; // for freeback the param
		$results_json_extract = [];
		$results_json_extract['finallyResult'] = '$.finallyResult';
		$rows = $this->getDetailListByWalletAccountId($walletAccountId, true, 'desc', $results_json_extract);
		return $rows;
	} // EOF isExistsInByTransCode

    public function isExistsInByTransCodeWithTableName($transCode, &$walletAccountId = null, &$dwDateTime = null){
        $this->load->model(array('Wallet_model'));
        if( is_null($walletAccountId)){
            $walletAccountDeatil = $this->wallet_model->getWalletAccountByTransactionCode($transCode);
            $walletAccountId = $walletAccountDeatil['walletAccountId'];
        }else{
            // $walletAccountId will directly to usd.
        }

		$dwDateTime = $walletAccountDeatil['dwDateTime']; // for freeback the param
		$results_json_extract = [];
		$results_json_extract['finallyResult'] = '$.finallyResult';
		$rows = $this->getDetailListByWalletAccountId($walletAccountId, true, 'desc', $results_json_extract);
		return $rows;
    }

	/**
	 * Get the records of queue_results by walletAccountId after dwDateTime.
	 *
	 * @param string $walletAccountId The field,"walletAccountId" of WalletAccount.
	 * @param string $dwDateTime The datetime.
	 * @return array The records of the data-table,"queue_results".
	 */
	public function isExistsInQueueResultsByWalletAccountId($walletAccountId, $dwDateTime){
		$this->load->model(array('Queue_result'));

		// $full_params = 'walletAccountId":'.$walletAccountId.'}';
		$full_params = null;
		// $params = 'walletAccountId":'.$walletAccountId.'}';
		$params = '{"walletAccountId":'.$walletAccountId.'}';
		$like_side = 'none';
		$func_name = 'remote_processPreChecker';
		$dwDateTimeDate = new DateTime($dwDateTime);
		$nowDate = new DateTime();
		$result = null;
		$created_at_range = [];
		$created_at_range[0] = $dwDateTimeDate->format('Y-m-d H:i:s');
		$created_at_range[1] = $nowDate->format('Y-m-d H:i:s');
		// `created_at` > '2021-01-27 11:00:00' AND `created_at` <= '2021-01-27 11:40:00'
		$order_by = [];
		$order_by['field'] = 'created_at';
		$order_by['by'] = 'desc';
		$resultList = $this->queue_result->getResultListByFuncNameAndFullParamsOrParams($func_name, $full_params, $result, $created_at_range, $order_by, $params, $like_side);
		return $resultList;
	}

	/**
	 * For Dispatch Withdrawal Condition List under a Definition.
	 *
	 * @param array $request The post params.
	 * @param array $permissions The return of BaseController::getContactPermissions().
	 * @param boolean $is_export for "Export CSV" of datatable() plug-in.
	 * @return array
	 */
	public function dataTablesList($request, $permissions, $is_export = false){
		$this->load->model(array('Wallet_model', 'dispatch_withdrawal_definition'));
		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
		$columns = [];
		$_this = $this;

        $_unionTableNames = [];
        if( $this->enabled_dispatch_withdrawal_results_monthly_table ){
            $_tableName4orig = $this->combineMonthlyTableName($this->base_on_tableName, '');
            $_tableName4now = $this->combineMonthlyTableName($this->base_on_tableName, 'now');
            $_tableName4pre = $this->combineMonthlyTableName($this->base_on_tableName, 'first day of previous month');

            $_unionTableNames[] = $_tableName4orig;
            $_unionTableNames[] = $_tableName4now;
            $_unionTableNames[] = $_tableName4pre;
            $theTableName = 'results_list';
        }else{
            $theTableName = $this->tableName;
        }
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'id',
			'select' => 'id',
			'name' => lang('lang.select'),
			'formatter' => function($d, $row) use ($is_export, &$_this){
                $return_d = '';
                if( $_this->enabled_dispatch_withdrawal_results_monthly_table ){
                    if($is_export){
                        // plain text
                        $return_d = sprintf('%s.%s', $row['splitted_table'], $d);
                    }else{
                        // html format
                        $return_d = sprintf('<span data-splitted_table="%s">%s</span>', $row['splitted_table'], $d);
                    }
                }else{
                    $return_d = $d; // plain text in id
                }
				return $return_d;
			},
		);
		$columns[] = array(
			// 'dt' => $i++,
			'alias' => 'walletAccountId',
			'select' => 'wallet_account_id',
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'definitionId',
			'select' => 'definition_id',
			'name' => lang('cms.title'),
			'formatter' => function($d, $row) use ($is_export){
				$theDetail = $this->dispatch_withdrawal_definition->getDetailById($d);

				return $theDetail['name'];
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'definitionResults',
			'select' => 'JSON_KEYS(definition_results)', // finallyResult
			'name' => lang('lang.result'),
			'formatter' => function($d, $row) use ($is_export, $theTableName, $_this){

				if( ! empty($d) ){
					$results_lite = [];
					$results_keys = $this->utils->json_decode_handleErr($d);
					$_id = $row['id'];
					$exception_key_list = [];
					$exception_key_list[] = 'finallyResult';
					$exception_key_list[] = 'conditions_name';
					$exception_key_list[] = 'conditions_id';
					// $exception_key_list[] = 'betAndWithdrawalRate'; // The item might cause the Fatal Error, "Allowed memory size exhausted".
				}

				if( ! empty($d) ){
                    if( ! $_this->enabled_dispatch_withdrawal_results_monthly_table ){
                        $_sql = <<<EOF
                                SELECT IFNULL( JSON_UNQUOTE(JSON_EXTRACT(definition_results, '$.finallyResult'))
                                        , NULL
                                ) AS finallyResult
                                , IFNULL( JSON_UNQUOTE(JSON_EXTRACT(definition_results, '$.conditions_name'))
                                    , NULL
                                ) AS conditions_name
                                , IFNULL( JSON_UNQUOTE(JSON_EXTRACT(definition_results, '$.conditions_id'))
                                    , NULL
                                ) AS conditions_id
                                FROM $this->tableName where id = ?
EOF;
                    }else{
                        $unionTableName = [];
                        $unionTableName[] = $row['splitted_table'];

                        $_selectFields = [];
                        $_selectFields[] = "IFNULL( JSON_UNQUOTE(JSON_EXTRACT(definition_results, '$.finallyResult') ), NULL ) AS finallyResult";
                        $_selectFields[] = "IFNULL( JSON_UNQUOTE(JSON_EXTRACT(definition_results, '$.conditions_name') ), NULL ) AS conditions_name";
                        $_selectFields[] = "IFNULL( JSON_UNQUOTE(JSON_EXTRACT(definition_results, '$.conditions_id') ), NULL ) AS conditions_id";
                        $_whereClauses = 'id = '. $_id;
                        $_sql = $_this->unionSubqueryWithSelectFromWhere($_selectFields, $unionTableName, $_whereClauses);

                    }

					$query = $this->db->query($_sql, [ $_id ]); // @todo 這邊應該不是只有換 $theTableName
					$_rlt = $query->row_array();
					unset($query); // free the object for memory
					$item = [];
					$item['finallyResult'] = $_rlt['finallyResult'];
					$item['conditions_name'] = $_rlt['conditions_name'];
					$item['conditions_id'] = $_rlt['conditions_id'];
					if( ! empty($item) ){
						$results_lite = array_merge($results_lite, $item);
					}
				}

				if( ! empty($d) ){
					foreach( $results_keys as $_keyStr){
						$item = [];
						if( ! in_array($_keyStr, $exception_key_list) ){

                            if( ! $_this->enabled_dispatch_withdrawal_results_monthly_table ){
                                $_sql = <<<EOF
                                SELECT IFNULL( JSON_UNQUOTE(JSON_EXTRACT(definition_results, '$.{$_keyStr}.result'))
                                        , NULL
                                ) AS {$_keyStr}_result
                                , IFNULL( JSON_UNQUOTE(JSON_EXTRACT(definition_results, '$.{$_keyStr}.formula'))
                                    , NULL
                                ) AS {$_keyStr}_formula
                                , IFNULL( JSON_UNQUOTE(JSON_EXTRACT(definition_results, '$.{$_keyStr}.isEnable'))
                                    , NULL
                                ) AS {$_keyStr}_isEnable

                                , IFNULL( JSON_TYPE( JSON_EXTRACT(definition_results, '$.{$_keyStr}.resultDetail') )
                                    , NULL
                                ) AS {$_keyStr}_hasResultDetail
                                , JSON_LENGTH(JSON_EXTRACT(definition_results, '$.{$_keyStr}')) AS {$_keyStr}_itemAmount

                                FROM $this->tableName where id = ?
EOF;
                            }else{
                                $unionTableName = [];
                                $unionTableName[] = $row['splitted_table'];// just for current splitted_table

                                $_selectFields = [];
                                $_selectFields[] = sprintf("IFNULL( JSON_UNQUOTE(JSON_EXTRACT(definition_results, '$.%s.result')), NULL) AS %s_result", $_keyStr, $_keyStr);
                                $_selectFields[] = sprintf("IFNULL( JSON_UNQUOTE(JSON_EXTRACT(definition_results, '$.%s.formula')), NULL) AS %s_formula", $_keyStr, $_keyStr);
                                $_selectFields[] = sprintf("IFNULL( JSON_UNQUOTE(JSON_EXTRACT(definition_results, '$.%s.isEnable')), NULL) AS %s_isEnable", $_keyStr, $_keyStr);
                                $_selectFields[] = sprintf("IFNULL( JSON_TYPE( JSON_EXTRACT(definition_results, '$.%s.resultDetail') ), NULL) AS %s_hasResultDetail", $_keyStr, $_keyStr);
                                $_selectFields[] = sprintf("JSON_LENGTH(JSON_EXTRACT(definition_results, '$.%s')) AS %s_itemAmount", $_keyStr, $_keyStr);
                                $_whereClauses = 'id = '. $_id;
                                $do_select_splitted_table = false;
                                $_sql = $_this->unionSubqueryWithSelectFromWhere($_selectFields, $unionTableName, $_whereClauses, $do_select_splitted_table);
                            }

							// {$_keyStr}_hasResultDetail for detect the object of the key is exists?
							// there have others item be hidden by each.
							// please hanlde by item and ticket.
							$query = $this->db->query($_sql, [ $_id ]);
							$_rlt = $query->row_array();
							unset($query); // free the object for memory

							if( ! is_null($_rlt["{$_keyStr}_result"]) ){
								$item['result'] = $_rlt["{$_keyStr}_result"];
							}
							if( ! is_null($_rlt["{$_keyStr}_formula"]) ){
								$item['formula'] = $_rlt["{$_keyStr}_formula"];
							}
							if( ! is_null($_rlt["{$_keyStr}_isEnable"]) ){
								$item['isEnable'] = $_rlt["{$_keyStr}_isEnable"];
							}
							if( ! empty($_rlt["{$_keyStr}_hasResultDetail"]) ){ // @TODO While need to see the detail, should create a ticket for this.
								$item['_hasResultDetail'] = $_rlt["{$_keyStr}_hasResultDetail"];
							}
							if( ! is_null($_rlt["{$_keyStr}_itemAmount"]) ){ // The amount means the number of the child amount.
								$item['_itemAmount'] = $_rlt["{$_keyStr}_itemAmount"];
							}
						} // EOF if( ! in_array($_keyStr, $exception_key_list) ){...

						if( ! empty($item) ){
							$results_lite[$_keyStr] = $item;
						}
					}
					$d = json_encode($results_lite, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
				}

				return $d;
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'result_dwStatus',
			'select' => 'result_dw_status',
			'name' => lang('Result Status'),
			'formatter' => function($d, $row) use ($is_export){
				$formatted = lang('N/A');
				if( ! empty($d) ){
					$formatted = $d; // @todo
				}
				return $d;
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'dispatch_order',
			'select' => 'dispatch_order',
			'name' => lang('Order'),
			'formatter' => function($d, $row) use ($is_export){
				return $d;
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'after_status',
			'select' => 'after_status',
			'name' => lang('Pushed Status'),
			'formatter' => function($d, $row) use ($is_export){
				$formatted = lang('N/A');
				if( ! empty($d) ){
					$formatted = $d; // @todo
				}
				return $formatted;
			},
		);

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'created_at',
			'select' => 'created_at',
			'name' => lang('Created At'),
		);

        /// get walletAccountId from request
        $walletAccountId = null;
        $this->load->library('data_tables');
		$input = $this->data_tables->extra_search($request);
		if (isset($input['transCode'])) {
			$transCode = $input['transCode'];
			$walletAccountDeatil =  $this->wallet_model->getWalletAccountByTransactionCode($transCode);
			$walletAccountId = $walletAccountDeatil['walletAccountId'];
        }


		# END DEFINE COLUMNS #################################################################################################################################################


        if( $this->enabled_dispatch_withdrawal_results_monthly_table ){
            $_selectFields = $this->collectColumnsOfDataTables($columns, function($_column){ // $callbackInForeach
                $_clause = '';
                if( $_column['select'] == 'JSON_KEYS(definition_results)' ){ // drop the function brackets, for select of outer union subquery
                    $_column['select'] = 'definition_results';
                }
                $_clause = $_column['select'];
                return $_clause;
            });
            $_whereClauses = [];
            if( ! empty($walletAccountId) ) {
                $_whereClauses[] = 'wallet_account_id = '.$walletAccountId;
            }else{
                $_whereClauses[] = '1';
            }

            $uinonTablesSubquery = $this->unionSubqueryWithSelectFromWhere($_selectFields, $_unionTableNames, $_whereClauses);

    		$table = " ( $uinonTablesSubquery ) as $theTableName ";

            // at the position after collectColumnsOfDataTables() called
            $columns[] = array(
                'alias' => 'splitted_table',
                'select' => 'splitted_table',
            );
        }else{
            $table = $this->tableName;
        }

		$joins = array();

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();

		$this->load->library('data_tables');
		$input = $this->data_tables->extra_search($request);
		if ( ! empty($walletAccountId) ) {
			$where[] = $theTableName. ".wallet_account_id = ?";
			$values[] = $walletAccountId;
		}
		# END PROCESS SEARCH FORM #################################################################################################################################################

		if($is_export){
			$this->data_tables->options['is_export']=true;
					// $this->data_tables->options['only_sql']=true;
			if(empty($csv_filename)){
				$csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
			}
			$this->data_tables->options['csv_filename']=$csv_filename;
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
        $result['last_query'] = $this->data_tables->last_query;

		if($is_export){
			//drop result if export
			return $csv_filename;
		}


		return $result;

	} // EOF list

} // EOF Dispatch_withdrawal_conditions_included_game_description
