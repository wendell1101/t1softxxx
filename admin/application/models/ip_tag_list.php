<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

class Ip_tag_list extends BaseModel {

    protected $tableName = "ip_tag_list";

    function __construct() {
        parent::__construct();
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



    public function getRowsByIp($ip_list){

        $ip_condition_format = ' ip = "%s" ';
		$where_fragments = [];
        if( ! empty($ip_list) ){
            foreach($ip_list as $_ip){
                $where_fragments[] = sprintf($ip_condition_format, $_ip );
            }
            $where = '('. implode(' OR ', $where_fragments). ')';
            $this->db->where($where);

        }

		// $this->db->where_in('evidence_status', [self::EVIDENCE_STATUS_ADDED, self::EVIDENCE_STATUS_UPDATED]);
		$query = $this->db->get($this->tableName);
		// $last_query = $this->db->last_query();

		return $this->getMultipleRowArray($query);
	}

    /**
	 * Will get all ip tags Only
	 *
	 * @return 	array
	 */
	public function getAllIpTagsOnly() {
		$query = $this->db->query("SELECT * FROM $this->tableName ");

		if (!$query->result_array()) {
			return [];
		} else {
			return $query->result_array();
		}
	}

    public function getIpTagsByIp($ip) {
        $query = $this->db->query("SELECT * FROM $this->tableName WHERE ip = ?", [$ip]);
		if (!$query->result_array()) {
			return [];
		} else {
			return $query->result_array();
		}
    }
}
