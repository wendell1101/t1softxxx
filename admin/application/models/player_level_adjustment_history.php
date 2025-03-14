<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Player_level_adjustment_history
 *
 */
class Player_level_adjustment_history extends BaseModel {
	protected $tableName = 'player_level_adjustment_history';

	public function __construct() {
		parent::__construct();
	}

	public function getAllowedFields(){
		return $this->db->list_fields($this->tableName);
	}
	/**
	 * Add a record
	 *
	 * @param array $params the fields of the table,"dispatch_withdrawal_results".
	 * @return void
	 */
	public function add($params) {
		$fields = $this->db->list_fields($this->tableName);

		$_data = array_filter($params, function($currValue , $indexStr) use ($fields) {

			return in_array($indexStr, $fields) !== false;
		}, ARRAY_FILTER_USE_BOTH);

		return $this->insertRow($_data);
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

} // EOF Player_level_adjustment_history
