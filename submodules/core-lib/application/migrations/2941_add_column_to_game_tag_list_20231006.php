<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_game_tag_list_20231006 extends CI_Migration {

    private $tableName = 'game_tag_list';

    public function up() {
        $field = [
            'expired_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ]
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            if (!$this->db->field_exists('expired_at', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $field);
                $this->load->model('player_model');
                $this->player_model->addIndex($this->tableName, 'idx_expired_at','expired_at');
            }
        }
    }

    public function down() {
        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('expired_at', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'expired_at');
            }
        }
	}
}