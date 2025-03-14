<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Dispatch_withdrawal_conditions_included_game_description
 *
 */
class Dispatch_withdrawal_conditions_included_game_description extends BaseModel {
	protected $tableName = 'dispatch_withdrawal_conditions_included_game_description';

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
	 * delete a record by conditions_id
	 *
	 * @param integer $conditions_id The field,"dispatch_withdrawal_conditions.id".
	 * @return boolean If true means delete the record completed else false means failed.
	 */
	public function deleteByConditionsId($conditions_id){
		$this->db->where('dispatch_withdrawal_conditions_id', $conditions_id);
		return $this->runRealDelete($this->tableName);
	} // EOF deleteByConditionsId

	/**
	 * Get the rows by conditions_id
	 *
	 * @param integer $conditions_id The field,"dispatch_withdrawal_conditions.id".
	 * @return array The rows.
	 */
	public function getDetailListByConditionsId($conditions_id){
		$this->db->select('*')
			->from($this->tableName)
			->where('dispatch_withdrawal_conditions_id', $conditions_id);

		$result = $this->runMultipleRowArray();

		return $result;
	}// EOF getDetailListByConditionsId


	/**
     * Parse the Game string of jstree
     *
     * @param string $theGameStr The game string for Identification game, the format,
     * - gp_XXX mean the game platform alias external_system(), system_type=1 ).
     * - gp_XXX_gt_ mean the game type.
     * - gp_XXX_gt_YYY_gd mean the game description.
     * @return array The game info,
     * - $return[0] The game platform id.
     * - $return[1] The game type id, if zero means No data.
     * - $return[2] The game description id, if zero means No data.
     */
    public function parseGameStrOfJstree($theGameStr){
        /**
         * reference to https://regex101.com/r/LtkxiN/1/
         */
        $re = '/gp_(?P<game_platform_id>\d+)((_gt_(?P<game_type_id>\d+))?_gd_(?P<game_description_id>\d+))?/m';
        preg_match_all($re, $theGameStr, $matches, PREG_SET_ORDER, 0);

        return array( $matches[0]['game_platform_id']
                    , empty($matches[0]['game_type_id'])? 0:$matches[0]['game_type_id']
                    , empty($matches[0]['game_description_id'])? 0:$matches[0]['game_description_id']
                );
    }// EOF parseGameStrOfJstree

} // EOF Dispatch_withdrawal_conditions_included_game_description
