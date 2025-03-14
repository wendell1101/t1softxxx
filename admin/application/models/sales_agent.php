<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * sales_agent
 */
class Sales_agent extends BaseModel {
	protected $tableName = 'admin_sales_agent';
	protected $playerTableName = 'player_sales_agent';

	const DEACTIVE_SALES_AGENT = 0;

    const ACTIVE_SALES_AGENT  = 1;

	const DEACTIVE_PLAYER_SALES_AGENT = 0;


	public function __construct() {
		parent::__construct();
	}

	/**
     * insertDuplicateContactNumberHistory
	 * @param array $data 
	 * @return boolean
	 */
	public function addSalesAgent($data, $params = array()) {
		if (!empty($data)) {
			$data = array_merge($data, $params);
			return $this->insertRow($data);
		}
		return false;
	}

	public function updateSalesAgent($id, $data = array() ) {
        return $this->updateRow($id, $data);
    }


	public function getSalesAgentDetailById($id, $field = false) {

		$query = $this->db->get_where($this->tableName, array('user_id' => $id));
        if ($field) {
            return $this->getOneRowOneField($query,  $field);
        } else {
            return $this->getOneRowArray($query);
        }
	}

	public function getAllSalesAgentDetail() {
		$this->db->select('admin_sales_agent.*, adminusers.username')
			->from($this->tableName)
			->join('adminusers', 'adminusers.userId = admin_sales_agent.user_id', 'left')
			->where('admin_sales_agent.status', self::ACTIVE_SALES_AGENT);
		return $this->runMultipleRowArray();
	}

	public function getPlayerSalesAgentDetailById($id) {
		$this->db->select('player_sales_agent.*, adminusers.username,adminusers.realname,admin_sales_agent.chat_platform1, admin_sales_agent.chat_platform2')
			->from('player_sales_agent')
			->join('admin_sales_agent', 'admin_sales_agent.id = player_sales_agent.sales_agent_id', 'left')
			->join('adminusers', 'adminusers.userId = admin_sales_agent.user_id', 'left')
			->where('player_id', $id);
		return $this->runOneRowArray();
	}

	public function getPlayerSalesAgentBySalesAgentId($id) {
		$this->db->select('*')
			->from($this->playerTableName)
			->where('sales_agent_id', $id);
		return $this->runMultipleRowArray();
	}


	public function addPlayerSalesAgent($data, $params = array()) {
		if (!empty($data)) {
			$data = array_merge($data, $params);
			return $this->insertData($this->playerTableName, $data);
		}
		return false;
	}

	public function updatePlayerSalesAgent($id, $data = array() ) {
		if (!empty($data)) {
			$this->db->set($data)->where('player_id', $id)->update($this->playerTableName);
		}
	}
}
