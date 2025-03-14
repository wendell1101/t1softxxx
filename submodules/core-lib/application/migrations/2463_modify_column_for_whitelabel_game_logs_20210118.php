<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_whitelabel_game_logs_20210118 extends CI_Migration {

    private $tableName='whitelabel_game_logs';

    public function up() {
        $field = array(
            'match' => array(
                'type' => 'VARCHAR',
				'constraint' => '500',
				'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('match', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $field);
            }
        }
    }

    public function down() {
    }
}