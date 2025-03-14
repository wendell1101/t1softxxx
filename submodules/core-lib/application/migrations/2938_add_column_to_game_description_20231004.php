<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_game_description_20231004 extends CI_Migration {

    private $tableName = 'game_description';

    public function up() {
        $field = [
            'bonus_tag' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ]
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            if (!$this->db->field_exists('bonus_tag', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $field);
                $this->load->model('player_model');
                $this->player_model->addIndex($this->tableName, 'idx_bonus_tag','bonus_tag');
            }
        }
    }

    public function down() {
        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('bonus_tag', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'bonus_tag');
            }
        }
	}
}