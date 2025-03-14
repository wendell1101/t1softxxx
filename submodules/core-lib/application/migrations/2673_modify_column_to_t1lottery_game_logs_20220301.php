<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_to_t1lottery_game_logs_20220301 extends CI_Migration {

    private $tableName='t1lottery_game_logs';

    public function up() {
        $field = array(
            'uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)) {
            if($this->db->field_exists('uniqueid', $this->tableName)) {
                $this->dbforge->modify_column($this->tableName, $field);
            }
        }
    }

    public function down() {
    }
}