<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_summary2_report_daily_20200601 extends CI_Migration {

    private $tableName = 'summary2_report_daily';

    public function up() {
        $fields = array(
            'total_player_fee' => array(
                'type' => 'DOUBLE',
            ),
        );

        if(!$this->db->field_exists('total_player_fee', $this->tableName)){
            $this->load->model('player_model');
            $this->dbforge->add_column($this->tableName, $fields, 'total_fee');
        }
    }

    public function down() {
        if($this->db->field_exists('total_player_fee', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'total_player_fee');
        }
    }
}