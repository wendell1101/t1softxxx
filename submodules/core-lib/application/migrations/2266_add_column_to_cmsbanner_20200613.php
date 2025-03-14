<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// class Migration_add_column_to_summary2_report_daily_20200601 extends CI_Migration {
class Migration_add_column_to_cmsbanner_20200613 extends CI_Migration {

    private $tableName = 'cmsbanner';

    public function up() {
        $fields = array(
            'extra' => array(
				'type' => 'JSON',
				'null' => true
            ),
        );

        if(!$this->db->field_exists('extra', $this->tableName)){
            // $this->load->model('player_model');
            $this->dbforge->add_column($this->tableName, $fields, 'link_target');
        }
    }

    public function down() {
        if($this->db->field_exists('extra', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'extra');
        }
    }
}
