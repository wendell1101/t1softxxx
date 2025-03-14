<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * duplicate_contactnumber_model
 *
 *
 */
class Duplicate_contactnumber_model extends BaseModel {
	protected $tableName = 'player_duplicate_contactnumber';


	public function __construct() {
		parent::__construct();
	}

	/**
     * insertDuplicateContactNumberHistory
	 * @param array $data 
	 * @return array
	 */
	public function insertDuplicateContactNumberHistory($data, $params = array()) {
		if (!empty($data)) {
			return $this->insertRow($data);
		}
		return false;
	}

    public function countDuplicateContactNumber($start_today = null,$end_today = null) {
		$this->db->select('count(id) as cnt');
		$this->db->from($this->tableName);
		if(!empty($start_today) && !empty($end_today)){
			$this->db->where('created_at >=', $start_today);
			$this->db->where('created_at <=', $end_today);
		}

		return $this->runOneRowOneField('cnt');
	}
}
