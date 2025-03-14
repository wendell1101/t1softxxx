<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_change_column_on_player_oauth2_20210717 extends CI_Migration {

    public function up() {
        $tableName='player_oauth2_clients';
        $field = array(
            'user_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
            ),
        );
        if($this->db->field_exists('user_id', $tableName)){
            $this->dbforge->modify_column($tableName, $field);
        }

        $tableName='player_oauth2_access_tokens';
        if($this->db->field_exists('user_id', $tableName)){
            $this->dbforge->modify_column($tableName, $field);
        }

        // ====player_oauth2_refresh_tokens=================================
        $tableName='player_oauth2_refresh_tokens';
        $field = [
            "id" => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
        ];
        if($this->db->field_exists('id', $tableName)){
            $this->dbforge->modify_column($tableName, $field);
        }
        // ====player_oauth2_refresh_tokens=================================
    }

    public function down() {
    }
}