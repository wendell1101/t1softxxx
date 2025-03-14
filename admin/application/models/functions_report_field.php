<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Role Functions
 *
 * This model represents functions, rolefunctions and rolefunctions_giving data. It operates the following tables:
 * - functions
 * - rolefunctions
 * - rolefunctions_giving
 *
 * General Behavior
 * * get/insert/delete/isExists functions
 *
 * @category System Model
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */

class Functions_report_field extends BaseModel {

	function __construct() {
		parent::__construct();
	}

    public function getReportField($role_id,$funcCode)
    {
        $this->db->from('functions_report_field');
        $this->db->where('roleId', $role_id);
        $this->db->where('funcCode', $funcCode);

        return $this->db->get()->row_array();
	}

	public function getFunctionPermission($roleId, $funcCode)
	{
		$result = [
			'exist' => false,
			'permission' => [],
		];
		
		
		if ($roleId == 0) {
			return $result;
		}

		$this->db->select('fields')->from('functions_report_field');
		$this->db->where('roleId', $roleId);
		if (!empty($funcCode)) {
			$this->db->where('funcCode', $funcCode);
		}

		$row = $this->db->get()->row();

		if (!empty($row)) {
			$result['permission'] = explode(',', $row->fields);
			$result['exist'] = true;
		}
		
		return $result;
	}

	public function updateSummaryReport($role_id, $funcCode, $summaryFields) {
		$result = $this->db->replace('functions_report_field',[
			'roleId' => $role_id,
			'funcCode' => $funcCode,
			'fields' => !empty($summaryFields)? implode(',',$summaryFields):''
		]);

		return $result;

	}

	public function updateReportFieldPermission($role_id, $funcCode, $fields) {
		$result = $this->db->replace('functions_report_field',[
			'roleId' => $role_id,
			'funcCode' => $funcCode,
			'fields' => !empty($fields)? implode(',',$fields):''
		]);

		return $result;

	}

	public function getPermissionWithKey($roleId) {
		$permission = [];
		
		$this->db->from('functions_report_field');
		$this->db->where('roleId', $roleId);

		$rows = $this->db->get()->result_array();

		if (!empty($rows)) {
			foreach ($rows as $value) {
				$permission[$value['funcCode']] = explode(',',$value['fields']);
			}
		}

		return $permission;	
	}

	public function checkRoleFunctionsFieldsExist($roleId, $funcCode) {
		$this->db->from('functions_report_field');
		$this->db->where('roleId', $roleId);
		$this->db->where('funcCode', $funcCode);


		return ($this->db->get()->num_rows() > 0)? true : false;
	}
}
