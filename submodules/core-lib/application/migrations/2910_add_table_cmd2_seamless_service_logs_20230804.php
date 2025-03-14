<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_cmd2_seamless_service_logs_20230804 extends CI_Migration {

    private $tableName = 'cmd2_seamless_service_logs';

    public function up() {
        if(!$this->db->table_exists($this->tableName)){
            $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL("CREATE TABLE {$this->tableName} LIKE cmd_seamless_service_logs");
        }
    }

    public function down() {
        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}