<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_hbk_game_logs_20220310 extends CI_Migration {

    private $tableName ='hkb_game_logs';

    public function up() {
        $fields = array(
            'username' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
        );
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('username', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $fields);
            }
        }
    }
    public function down() {

    }
}

