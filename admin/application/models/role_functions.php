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

class Role_functions extends BaseModel {

	function __construct() {
		parent::__construct();
	}

    /**
	 * This function returns all records as an array
     * under the 'functions' table
     *
	 * @return array
	 */
	public function getFunctions() {
		return $this->db->get('functions');
	}

    /**
	 * This function returns a boolean stating if the
     * passed $func_id exists in the 'functions' table
     *
     * @param int $func_id
	 * @return boolean
	 */
	public function functionExists($func_id = null){
        return $this->db->where('funcId', $func_id)->get('functions')->num_rows() > 0;
    }

    /**
	 * This function returns an array of records from the
     * 'rolefunctions' table
     *
     * @param array $func_ids
	 * @return array
	 */
	public function getRoleFunctionsByFuncId($func_ids){
        if(is_array($func_ids)) {
            $this->db->where_in('funcId', $func_ids);
        } else {
            $this->db->where('funcId', $func_ids);
        }
        return $this->db->get('rolefunctions')->result_array();
    }

    /**
	 * This function returns an array of records from the
     * 'rolefunctions_giving' table
     *
     * @param array $func_ids
	 * @return array
	 */
	public function getRoleFunctionsGivingByFuncId($func_ids){
        if(is_array($func_ids)) {
            $this->db->where_in('funcId', $func_ids);
        } else {
            $this->db->where('funcId', $func_ids);
        }
        return $this->db->get('rolefunctions_giving')->result_array();
    }

    /**
	 * This function returns a boolean stating if the
     * passed $func_id with $role_id exists in the 'rolefunctions' table
     *
     * @param int $func_id
     * @param int $role_id
	 * @return boolean
	 */
	public function roleFunctionExistsByRoleId($func_id = null, $role_id = null){
        $this->db->where('funcId', $func_id);
        $this->db->where('roleId', $role_id);
        return $this->db->get('rolefunctions')->num_rows() > 0;
    }

    /**
	 * This function returns a boolean stating if the
     * passed $func_id with $role_id exists in the 'rolefunctions_giving' table
     *
     * @param int $func_id
     * @param int $role_id
	 * @return boolean
	 */
	public function roleFunctionGivingExistsByRoleId($func_id = null, $role_id = null){
        $this->db->where('funcId', $func_id);
        $this->db->where('roleId', $role_id);
        return $this->db->get('rolefunctions_giving')->num_rows() > 0;
    }


    /**
	 * This function inserts the passed $insert_data array
     * into the 'rolefunctions' table
     *
     * @param array $insert_data
	 * @return boolean
	 */
	public function insertIntoRoleFunctions($insert_data){
        return $this->db->insert('rolefunctions', $insert_data);
    }

    /**
	 * This function inserts the passed $insert_data array
     * into the 'rolefunctions_giving' table
     *
     * @param array $insert_data
	 * @return boolean
	 */
	public function insertIntoRoleFunctionsGiving($insert_data){
        return $this->db->insert('rolefunctions_giving', $insert_data);
    }

    /**
	 * This function deletes the passed $delete_data array
     * from the 'rolefunctions' table
     *
     * @param array $delete_data
	 * @return boolean
	 */
	public function deleteFromRoleFunctions($delete_data){
        return $this->db->delete('rolefunctions', $delete_data);
    }

    /**
	 * This function inserts the passed $data array
     * from the 'rolefunctions_giving' table
     *
     * @param array $delete_data
	 * @return boolean
	 */
	public function deleteFromRoleFunctionsGiving($delete_data){
        return $this->db->delete('rolefunctions_giving', $delete_data);
    }

}

/* End of file role_functions.php */
/* Location: ./application/models/role_functions.php */
