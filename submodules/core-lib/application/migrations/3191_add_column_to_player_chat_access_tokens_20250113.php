<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_player_chat_access_tokens_20250113 extends CI_Migration {

    private $tableName = 'player_chat_access_tokens';

    public function up() {
        $fields = [
            'language' => [
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ]
        ];

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('language', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('language', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'language');
            }
        }
    }
}
