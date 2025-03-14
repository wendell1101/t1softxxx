<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

class Insvr_game_description_log extends BaseModel {

    protected $tableName = 'insvr_game_description_log';

    function __construct() {
		parent::__construct();
    }

    /**
     * Add a record
     *
     * @param array $params the fields of the table,"insvr_log".
     * @return void
     */
    public function add($params) {
        $data = [];
        $data = array_merge($data, $params);
        return $this->insertRow($data);
    } // EOF add

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
	 * delete a record by insvr_log_id
	 *
	 * @param integer $insvr_log_id The field,"insvr_log.id".
	 * @return boolean If true means delete the record completed else false means failed.
	 */
	public function deleteByInsvrLogId($insvr_log_id){
		$this->db->where('insvr_log_id', $insvr_log_id);
		return $this->runRealDelete($this->tableName);
	} // EOF deleteByConditionsId
}