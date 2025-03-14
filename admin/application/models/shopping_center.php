<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

class Shopping_center extends BaseModel {

	function __construct() {
		parent::__construct();
	}

	protected $tableName = "shopping_center";

	public function insertShoppingCenterItem($data) {
		$this->insertData($this->tableName, $data);
	}

	public function getData($id = null, $status = null, $isHideFromPlayerCenter = 'showall') {		

		$where = '';
		$params = [];
		if($id){
			$where .= ' AND shopping_center.id = ?';
			$params[] = $id;
		}

		if ($isHideFromPlayerCenter != 'showall') {			
			$where .= ' AND shopping_center.hide_it_on_player_center = ?';
			$params[] = $isHideFromPlayerCenter;
		}

		if ($status) {			
			$where .= ' AND shopping_center.status = ?';
			$params[] = $status;
		}

$sql = <<<EOD
SELECT
shopping_center.id,
shopping_center.title,
shopping_center.short_description,
shopping_center.details,
shopping_center.requirements,
shopping_center.how_many_available,
shopping_center.tag_as_new,
shopping_center.status,
shopping_center.created_at,
shopping_center.updated_at,
shopping_center.updated_by,
shopping_center.banner_url,
shopping_center.hide_it_on_player_center,
admusr1.username as created_by,
admusr2.username as updated_by,
shopping_center.is_default_banner_flag,
shopping_center.item_order as item_order,
IF(shopping_center.item_order is null, 0,shopping_center.item_order) as item_order_zero, 
(shopping_center.item_order * -1) as item_order_negate
FROM shopping_center
LEFT JOIN adminusers as admusr1 on admusr1.userId = shopping_center.created_by
LEFT JOIN adminusers as admusr2 on admusr2.userId = shopping_center.updated_by
WHERE 1 {$where}
ORDER BY item_order_zero = 0, item_order_negate desc
EOD;

	$qry = $this->db->query($sql, $params);
	return $this->getMultipleRowArray($qry);
/*
		$this->db->select('
						shopping_center.id,
						shopping_center.title,
						shopping_center.short_description,
						shopping_center.details,
						shopping_center.requirements,
						shopping_center.how_many_available,
						shopping_center.tag_as_new,
						shopping_center.status,
						shopping_center.created_at,
						shopping_center.updated_at,
						shopping_center.updated_by,
						shopping_center.banner_url,
						shopping_center.hide_it_on_player_center,
						admusr1.username as created_by,
						admusr2.username as updated_by,
						shopping_center.is_default_banner_flag,
						shopping_center.item_order as item_order,
						IF(shopping_center.item_order is null, 0,shopping_center.item_order) as item_order_zero, 
						(shopping_center.item_order * -1) as item_order_negate');
		$this->db->join('adminusers as admusr1', 'admusr1.userId = shopping_center.created_by', 'left');
		$this->db->join('adminusers as admusr2', 'admusr2.userId = shopping_center.updated_by', 'left');
		$id ? $this->db->where('id', $id) : "";

		if ($isHideFromPlayerCenter != 'showall') {
			$this->db->where('shopping_center.hide_it_on_player_center', $isHideFromPlayerCenter);
		}

		$status ? $this->db->where('shopping_center.status', $status) : "";

		//order 1-max, null and 0 to end
		$this->db->order_by('item_order_zero = 0, item_order_negate desc');

		$query = $this->db->get();
		
		return $this->getMultipleRowArray($query);
		*/
	}

	public function updateItemData($data, $null1=null, $null2=null, $null3=null) {
		$this->db->set($data);
		$this->db->where("id", $data['id']);
		return $this->runAnyUpdate($this->tableName);
	}

	/**
	 * detail: Will get item details
	 *
	 * @param int $id
	 * @return array
	 */
	public function getItemDetails($id) {
		$this->db->from($this->tableName);
		$this->db->where('id', $id);		
		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * detail: activate shopping center item
	 *
	 * @param array $data
	 * @return array
	 */
	public function activateShoppingCenterItem($data) {
		$this->db->where('id', $data['id']);
		$this->db->update($this->tableName, $data);
	}

	/**
	 * detail: Will delete shopping center item
	 *
	 * @param int $id
	 * @return Boolean
	 */
	public function deleteItem($id) {
		$this->db->where('id', $id);
		$this->db->delete($this->tableName);
	}

	public function getExcelReport() {
		$this->db->from($this->tableName);
		$this->db->select('
						shopping_center.id as cnt,
						shopping_center.title,
						shopping_center.short_description,
						shopping_center.requirements as required_points,
						shopping_center.how_many_available,
						shopping_center.tag_as_new,
						shopping_center.status,
						shopping_center.created_at,
						shopping_center.updated_at,
						shopping_center.updated_by,
						admusr1.username as created_by,
						admusr2.username as updated_by,
							');
		$this->db->join('adminusers as admusr1', 'admusr1.userId = shopping_center.created_by', 'left');
		$this->db->join('adminusers as admusr2', 'admusr2.userId = shopping_center.updated_by', 'left');
		$query = $this->db->get();
		$cnt = 0;
		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$row['required_points'] = json_decode($row['required_points'], true)['required_points'];
				$row['updated_at'] = $row['updated_at'] ?: "N/A";
				$row['updated_by'] = $row['updated_by'] ?: "N/A";
				$row['tag_as_new'] = $row['tag_as_new'] ? lang("Yes") : lang("No");
				$row['status'] = $row['status'] ? lang("Active") : lang("Inactive");
				$cnt++;
				$row['cnt'] = $cnt;
				$data[] = $row;
			}
			return $data;
		}
		return false;
	}

}
