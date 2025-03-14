<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_player_latest_game_logs_20230626 extends CI_Migration {
    private $tableName = 'player_latest_game_logs';

    public function up() {
        $fields = [
            'round_id' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ],
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            if (!$this->db->field_exists('round_id', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $fields);
                $this->load->model('player_model');
                $this->player_model->addIndex($this->tableName, 'idx_round_id', 'round_id');
            }
        }
    }

    public function down() {
        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('round_id', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'round_id');
            }
        }
    }
}
