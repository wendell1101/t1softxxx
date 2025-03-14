<?php
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

class Game_seamless_service_logs extends BaseModel {
    public function __construct() {
        parent::__construct();
    }

    public function save($table, $query_type, $data = [], $field_name = null, $field_value = null, $update_with_result = false) {
        return $this->insertOrUpdateData($table, $query_type, $data, $field_name, $field_value, $update_with_result);
    }

    public function isMd5SumExist($table, $md5_sum) {
        $this->db->from($table)->where('md5_sum', $md5_sum);
        return $this->runExistsResult();
    }

    public function getCallCount($table, $md5_sum) {
        $this->db->select('call_count')->from($table)->where('md5_sum', $md5_sum);
        return $this->runOneRowOneField('call_count');
    }
}