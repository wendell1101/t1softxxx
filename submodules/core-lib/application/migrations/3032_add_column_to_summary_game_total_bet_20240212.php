<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_summary_game_total_bet_20240212 extends CI_Migration {
    private $tableName = 'summary_game_total_bet';

    public function up() {
        $field = [
            'external_game_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => true,
            ),
            'virtual_game_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => true,
            ),
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            $this->load->model('player_model');

            if (!$this->db->field_exists('external_game_id', $this->tableName) && !$this->db->field_exists('virtual_game_id', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $field);
            }


            $this->player_model->addIndex($this->tableName, 'idx_external_game_id', 'external_game_id');
            $this->player_model->addIndex($this->tableName, 'idx_virtual_game_id', 'virtual_game_id');
        }
    }

    public function down() {
        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('external_game_id', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'external_game_id');
            }
            if ($this->db->field_exists('virtual_game_id', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'virtual_game_id');
            }
        }
    }
}
///END OF FILE/////