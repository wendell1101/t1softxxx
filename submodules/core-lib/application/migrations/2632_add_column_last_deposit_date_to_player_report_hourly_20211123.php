<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_last_deposit_date_to_player_report_hourly_20211123 extends CI_Migration {

    private $tableName = 'player_report_hourly';

    public function up() {
        // $field = array(
        //    'last_deposit_date' => array(
        //         'type' => 'DATETIME',
        //         'null' => true
        //     ),
        // );

        // if(!$this->db->field_exists('last_deposit_date', $this->tableName)){
        //     $this->dbforge->add_column($this->tableName, $field);
        // }

    }

    public function down() {
        // if($this->db->field_exists('last_deposit_date', $this->tableName)) {
        //     $this->dbforge->drop_column($this->tableName, 'last_deposit_date');
        // }
    }
}
