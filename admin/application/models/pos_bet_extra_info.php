<?php
require_once dirname(__FILE__) . '/base_model.php';

class Pos_bet_extra_info extends BaseModel {
    protected $tableName = 'pos_bet_extra_info';

    public function __construct() {
        parent::__construct();
    }

    public function save($query_type, $data = [], $field_name = null, $field_value = null, $update_with_result = false) {
        return $this->insertOrUpdateData($this->tableName, $query_type, $data, $field_name, $field_value, $update_with_result);
    }

    public function isPosRecordExist($fields = []) {
        return $this->isRecordExist($this->tableName, $fields);
    }
}