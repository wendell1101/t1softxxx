<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_summary_game_total_bet_20240214 extends CI_Migration {
    private $tableName = 'summary_game_total_bet';

    public function up() {
        $field = [
            'currency_key' => array(
                'type' => 'VARCHAR',
                'constraint' => '5',
                'null' => true,
            ),
            'api_date' => array(
                'type' => 'DATE',
                'null' => true,
            ),
            'unique_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => true,
            ),
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            $this->load->model('player_model');
            if (!$this->db->field_exists('currency_key', $this->tableName) && !$this->db->field_exists('api_date', $this->tableName) && !$this->db->field_exists('unique_id', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $field);
            }
            $this->player_model->addUniqueIndex($this->tableName, 'idx_unique_id', 'unique_id');
        }
    }

    public function down() {
        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('currency_key', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'currency_key');
            }
            if ($this->db->field_exists('api_date', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'api_date');
            }
            if ($this->db->field_exists('unique_id', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'unique_id');
            }
        }
    }
}
///END OF FILE/////