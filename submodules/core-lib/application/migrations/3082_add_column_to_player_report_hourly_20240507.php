<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_player_report_hourly_20240507 extends CI_Migration {

    private $tableName = 'player_report_hourly';

    public function up() {
        $field = array(
           'subtract_balance' => array(
                'type' => 'DOUBLE',
                'null' => false,
                'default' => 0,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('subtract_balance', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('subtract_balance', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'subtract_balance');
            }
        }
    }
}
