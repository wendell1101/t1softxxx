<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';


class Dispatch_withdrawal_conditions extends BaseModel {
	protected $tableName = 'dispatch_withdrawal_conditions';

	protected $extraDefaults = [];

	public function __construct() {

		$this->extraDefaults['noDepositWithPromo_isEnable'] = '0';
		$this->extraDefaults['noAddBonusSinceTheLastWithdrawal_isEnable'] = '0';
		$this->extraDefaults['noDuplicateFirstNames_isEnable'] = '0';
		$this->extraDefaults['noDuplicateLastNames_isEnable'] = '0';
		$this->extraDefaults['noDuplicateAccounts_isEnable'] = '0';
		$this->extraDefaults['afterDepositWithdrawalCount_isEnable'] = '0';
		$this->extraDefaults['afterDepositWithdrawalCount_limit'] = '23';
		$this->extraDefaults['afterDepositWithdrawalCount_symbol'] = '-2';
		$this->extraDefaults['excludedPlayerLevels_isEnable'] = '0';
		$this->extraDefaults['excludedPlayerLevel_list'] = '0';
		$this->extraDefaults['thePlayerHadExistsInIovation_isEnable'] = '0';
		$this->extraDefaults['theTotalBetGreaterOrEqualRequired_isEnable'] = '0';
		parent::__construct();
	}

	/**
	 * Pick Extra out from Row and merge into the Row.
	 *
	 * @param array $row The row array.
	 * @return array $row The merged Row.
	 */
	public function pickExtraFromRow($row){
		$extra = [];
		if( ! empty($row['extra'] ) ){
			$extra = json_decode($row['extra'], true);
		}
		$row = array_merge($row, $extra);
		return $row;
	} // EOF pickExtraFromRow
	/**
	 * Find matches by shelf::extraDefaults
	 *
	 * @param array $params Usually with $_REQUEST.
	 * @return array The 2-way array,
	 * - The matches array
	 * - The array removed matches from $params.
	 */
	public function catchMatchsInExtraDefaults($params){
		$matchs = [];
		$filtedParams = [];
		$defaultsKeyList = array_keys($this->extraDefaults);
		foreach($params as $keyString => $valString){
			if( in_array($keyString, $defaultsKeyList) == true){
				// handle json_encode into the extra
				$matchs[$keyString] = $valString;
			}else{
				$filtedParams[$keyString] = $valString;
			}
		}
		return [$matchs, $filtedParams];
	} // EOF findMatchsInExtraDefaults

	/**
	 * Add a record
	 *
	 * @param array $params the fields of the table,"dispatch_withdrawal_definition".
	 * @return void
	 */
	public function add($params = array() ) {

		// handle for extra field.
		list($extra, $filtedParams) = $this->catchMatchsInExtraDefaults($params);
		$extraJsonStr = json_encode($extra);
		$filtedParams['extra'] = $extraJsonStr;

		$nowForMysql = $this->utils->getNowForMysql();
		$data['created_at'] = $nowForMysql;
		$data['updated_at'] = $nowForMysql;
		$data = array_merge($data, $filtedParams);
		return $this->insertRow($data);
	}// EOF add

	/**
	 * Update record by id
	 *
	 * @param integer $id
	 * @param array $data The fields for update.
	 * @return boolean|integer The affected_rows.
	 */
	public function update($id, $data = array() ) {


		// handle for extra field.
		list($extra, $filtedParams) = $this->catchMatchsInExtraDefaults($data);
		$extraJsonStr = json_encode($extra);
		$filtedParams['extra'] = $extraJsonStr;

		$nowForMysql = $this->utils->getNowForMysql();
		$filtedParams['updated_at'] = $nowForMysql;
		return $this->updateRow($id, $filtedParams);
	} // EOF update

	/**
	 * Delete a record by id(P.K.)
	 *
	 * @param integer $id The id field.
	 * @return boolean Return true means delete the record completed else false means failed.
	 */
	public function delete($id){
		$this->db->where('id', $id);
		return $this->runRealDelete($this->tableName);
	} // EOF delete

	/**
	 * Get a record by id(P.K.)
	 *
	 * @param integer $id The id field.
	 * @param boolean $pickFromExtra If true thats mean to parse and merge extra json string into the result.
	 * @return array The fields of the record.
	 */
	public function getDetailById($id, $pickFromExtra = false) {
		$this->db->select('*')
				->from($this->tableName)
				->where('id', $id);

		$result = $this->runOneRowArray();

		if($pickFromExtra){
			$result = $this->pickExtraFromRow($result);
			// $extra = [];
			// if( ! empty($result['extra'] ) ){
			// 	$extra = json_decode($result['extra'], true);
			// }
			// $result = array_merge($result, $extra);
		}
		return $result;
	}// EOF getDetailById

	/**
	 * Get the rows under a definition_id.
	 *
	 * @param integer $definition_id The field,"dispatch_withdrawal_definition.id".
	 * @param boolean $getEnabledOnly filter the inactived rows.
	 * @return array The rows under a "dispatch_withdrawal_definition.id".
	 */
	public function getDetailListByDefinitionId($definition_id, $getEnabledOnly = false, $pickFromExtra = false){
		$this->db->select('*')
			->from($this->tableName)
			->where('dispatch_withdrawal_definition_id', $definition_id);
		if($getEnabledOnly){
			$this->db->where('status', BaseModel::DB_TRUE);
		}

		$result = $this->runMultipleRowArray();

		if($pickFromExtra){
			foreach($result as $rowIndexNumber => $row){
				$result[$rowIndexNumber] = $this->pickExtraFromRow($row);
			}
		}
		return $result;
	}// EOF getDetailListByDefinitionId

	/// @todo
	//
	// symbol:
	// 1:greaterThanOrEqualTo, ≥
	// 2:greaterThan, >
	// 0:equalTo, =
	// -1:lessThanOrEqualTo, ≤
	// -2:lessThan, <
	public function formulaCompose($d, $row, $is_export){


		return $d;
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
		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
		$columns = [];
		$_this = $this;

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'id',
			'select' => $this->tableName. '.id',
			'name' => lang('lang.select'),
			'formatter' => function($d, $row) use ($is_export){
				return $d;
			},
		);
		$columns[] = array(
			// 'dt' => $i++,
			'alias' => 'dispatch_withdrawal_definition_id',
			'select' => $this->tableName. '.dispatch_withdrawal_definition_id',
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'name',
			'select' => $this->tableName. '.name',
			'name' => lang('lang.title'),
			'formatter' => function($d, $row) use ($is_export){
				return $d;
			},
		);
		// $columns[] = array(
		// 	'dt' => $i++,
		// 	'alias' => 'id',
		// 	'select' => $this->tableName. '.id',
		// 	'name' => lang('lang.formula'),
		// 	'formatter' => function($d, $row) use ($is_export, $_this){
		// 		// @todo OGP-18088 formula combian
		// 		return $_this->formulaCompose($d, $row, $is_export);
		// 	},
		// );

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'status',
			'select' => $this->tableName. '.status',
			'name' => lang('lang.status'),
			'formatter' => function($d, $row) use ($is_export){
				if($d == self::DB_TRUE){
					$formatted = lang('lang.activate');
				}else{
					$formatted = lang('lang.deactivate');
				}
				return $formatted;
			},
		);

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'created_at',
			'select' => $this->tableName. '.created_at',
			'name' => lang('Created At'),
		);

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'updated_at',
			'select' => $this->tableName. '.updated_at',
			'name' => lang('Updated At'),
		);

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'action',
			'select' => $this->tableName. '.id',
			'name' => lang('lang.action'),
			'formatter' => function($d, $row) use ($is_export){
				$formatted = '';
				$lang4delete = lang('cms.delete');
				$lang4edit = lang('cms.edit');
				$id = $d;
				// @todo OGP-18088, HTML script should moved into the js file.
				$html = <<<EOF
<span tabindex="0" data-toggle="tooltip" title="$lang4edit"  data-placement="top">
	<button type="button"class="btn btn-default btn-xs editWithdrawalCondition">
		<span class="glyphicon glyphicon-edit" data-detail-id="$id" >
		</span>
	</button>
</span>
<span tabindex="0" data-toggle="tooltip" title="$lang4delete"  data-placement="top">
	<button type="button" class="btn btn-default btn-xs deleteWithdrawalCondition">
		<span class="glyphicon glyphicon-trash" data-detail-id="$id" >
		</span>
	</button>
</span>
EOF;
				$formatted .= $html;
				return $formatted;
			},
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = $this->tableName;
		$joins = array();

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();

		$this->load->library('data_tables');
		$input = $this->data_tables->extra_search($request);
		if (isset($input['definition_id'])) {
			$where[] = $this->tableName. ".dispatch_withdrawal_definition_id = ?";
			$values[] = $input['definition_id'];
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

		if($is_export){
			//drop result if export
			return $csv_filename;
		}

		return $result;

	} // EOF list



	/**
	 * Get the array for jstree for a condition.
	 * @todo Performance enhancement
	 *
	 * @param integer $conditionId The field,"dispatch_withdrawal_conditions.id"
	 * @param array $filterColumn
	 * @return void
	 */
	public function get_game_tree_by_condition($conditionId = '', $filterColumn=array()) {

		// $filterColumn = is_null($this->getQueryString()) ? array() : $this->getQueryString();
		$this->utils->debug_log('======================get_game_tree_by_condition getQueryString', $filterColumn);

		if ( ! empty($conditionId) ){
			$result = $this->getGameTreeById($conditionId, $filterColumn);
		} else {
			$this->load->model('promorules');
			$result = $this->promorules->getGameTreeForPromoRule($filterColumn); // for all game and no-preset selected.
		}
		return $result;
	} // EOF get_game_tree_by_condition


	/**
	 * for jstree, generate the structure (contains pre-set selected) for jstree by dispatch_withdrawal_conditions.id
	 *
	 * @param integer $conditionId dispatch_withdrawal_conditions.id
	 * @param array $filterColumn
	 * @return array The structure for jstree. reference to game_description_model::getGameTreeArray().
	 */
	public function getGameTreeById($conditionId, $filterColumn=array()) {

		$this->load->model(array('game_description_model'));

		list($gamePlatformList, $gameTypeList, $gameDescList) = $this->getGameTypeAndDescById($conditionId);

		$showGameDescTree = $this->config->item('show_particular_game_in_tree');

		return $this->game_description_model->getGameTreeArray($gamePlatformList, $gameTypeList, $gameDescList, false, $showGameDescTree, $filterColumn);
	}// EOF getGameTreeById

	/**
	 * Get the gameTypeList, gameTypeList and gameDescList data By dispatch_withdrawal_conditions.id for jstree ( via game_description_model::getGameTreeArray() ).
	 *
	 * @param integer $conditionId The field,"dispatch_withdrawal_conditions.id" .
	 * @return array The array for jstree,
	 * - $return[0] The game_platform,"external_system table by system_type=1" key-value, game_platform_id-percentage array.
	 * - $return[1] The game_type key-value, game_type_id-percentage array.
	 * - $return[2] The game_description key-value, game_description_id-percentage array.
	 */
	public function getGameTypeAndDescById($conditionId) {

		$this->db->select('game_description.game_platform_id,game_description.game_type_id, game_description.id as game_description_id')
			->from('dispatch_withdrawal_conditions')
			->join('dispatch_withdrawal_conditions_included_game_description', 'dispatch_withdrawal_conditions.id = dispatch_withdrawal_conditions_included_game_description.dispatch_withdrawal_conditions_id')
			->join('game_description', 'game_description.id = dispatch_withdrawal_conditions_included_game_description.game_description_id', 'left')
			->where('dispatch_withdrawal_conditions.id', $conditionId);
		$rows = $this->runMultipleRowArray();
		$gamePlatformList = array();
		$gameTypeList = array();
		$gameDescList = array();

		if (!empty($rows)) {
			foreach ($rows as $row) {
				$gamePlatformList[$row['game_platform_id']] = 0; //$row['game_platform_percentage'];
				$gameTypeList[$row['game_type_id']] = 0; //$row['game_type_percentage'];
				$gameDescList[$row['game_description_id']] = 0; //$row['game_desc_percentage'];
			}
		}

		return array($gamePlatformList, $gameTypeList, $gameDescList);
	} // EOF getGameTypeAndDescById

} // EOF Dispatch_withdrawal_conditions
