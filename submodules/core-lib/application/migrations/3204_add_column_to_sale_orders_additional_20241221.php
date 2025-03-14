<?php

defined('BASEPATH') OR exit('No direct script access allowed');
class Migration_add_column_to_sale_orders_additional_20241221 extends CI_Migration {
    private $tableName = 'sale_orders_additional';
    public function up() {

        $fields = array(
            "async_job_params" => array(
                "type" => "JSON",
                "null" => true
            ),
        );

        $this->load->model('player_model');
        if(!$this->db->field_exists('async_job_params', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }
    public function down() {
        if($this->db->field_exists('async_job_params', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'async_job_params');
        }
    }
}