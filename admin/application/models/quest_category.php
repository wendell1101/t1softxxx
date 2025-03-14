<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Depositpromo
 *
 * This model represents promo type.
 *
 */

class Quest_category extends BaseModel {

	protected $tableName = 'quest_category';

    const SYSTEM_MANUAL_PROMO_TYPE_NAME = '_SYSTEM_MANUAL';
	const SYSTEM_MANUAL_PROMO_RULE_NAME = '_SYSTEM_MANUAL';
	const SYSTEM_MANUAL_PROMO_CMS_NAME = '_SYSTEM_MANUAL';

    const QUEST_CATEGORY_ORDER_MAX_CHARACTERS = 3;
    const QUEST_CATEGORY_NAME_MAX_CHARACTERS = 100;
    const QUEST_CATEGORY_INTERNAL_REMARK_MAX_CHARACTERS = 300;


	function __construct() {
		parent::__construct();
	}

    /**
	 * overview : get quest category
	 * @return bool
    */
	public function getQuestCategory() {
		$qry = "SELECT q_c.*,admin1.username AS createdBy, admin2.username AS updatedBy
                FROM quest_category AS q_c
                LEFT JOIN adminusers AS admin1
                    ON admin1.userId = q_c.createdBy
                LEFT JOIN adminusers AS admin2
                    ON admin2.userId = q_c.updatedBy
                WHERE q_c.title != ?
                AND q_c.deleted <> 1
                ORDER BY q_c.questCategoryId DESC";
        $query = $this->db->query($qry, array(self::SYSTEM_MANUAL_PROMO_TYPE_NAME));

		if ($query->num_rows() > 0) {
			$data = $query->result_array();
			return $data;
		}
		return false;
	}

	/**
	 * add promo type
	 *
	 * @return	$array
	 */
	public function addQuestCategory($data) {
		$this->db->insert($this->tableName, $data);

		//checker
		if ($this->db->affected_rows()) {
			return $this->db->insert_id();
		}

		return FALSE;
	}

    public function getNextOrder() {
        $lastOrder = $this->getLastOrder();
        return $lastOrder += 1;
    }

    /**
     * detail: get the last order in the lists
     *
     * @return int
     */
    public function getLastOrder() {
        $this->db->select('sort')
                 ->from($this->tableName)
                 ->order_by('sort', 'desc')
                 ->limit(1);

        $qry = $this->db->get();
        $ord = $this->getOneRowOneField($qry, 'sort');
        if ($ord) {
            return intval($ord);
        }
        return self::DEFAULT_START_ORDER;
    }

    public function uploadQuestCategoryIcon($file) {
        $image = isset($file) ? $file : null;

        # Player does not select icon to upload
        if(empty($image['name'][0])) {
            return array('status' => 'success');
        }

        $fullpath = $this->utils->getQuestCategoryIconPath();

        $config = array(
            'allowed_types' => "jpg|jpeg|png|svg",
            'max_size'      => $this->utils->getMaxUploadSizeByte(),
            'overwrite'     => true,
            'remove_spaces' => true,
            'upload_path'   => $fullpath,
        );

        if (!empty($image)) {
            $this->load->library('multiple_image_uploader');
            $response = $this->multiple_image_uploader->do_multiple_uploads($image, $fullpath, $config);


            if (strtolower($response['status']) == "success") {
                return array('status' => 'success', 'fileName' => $response['filename'][0]);
            }

            if(strtolower($response['status']) == "fail"){
                return array('status' => 'error', 'msg' => $response['message']);
            }
        }

        return false;
    }

    /**
	 * getQuestCategoryDetails
	 *
	 * @return	$array
	 */
	public function getQuestCategoryDetails($questCategoryId) {
		$this->db->select('*')->from($this->tableName);
		$this->db->where('questCategoryId', $questCategoryId);
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
	}

    public function editQuestCategory($data) {
		$this->db->where('questCategoryId', $data['questCategoryId']);
		$this->db->update($this->tableName, $data);
	}

    public function checkQuestManager($questCategoryId) {
        $this->db->select('questCategoryId')->from('quest_manager');
        $this->db->where('questCategoryId', $questCategoryId);
        $this->db->where('deleted', 0);
        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            return true;
        }
        return false;
    }

    
}

/* End of file depositpromo.php */
/* Location: ./application/models/promo_type.php */
