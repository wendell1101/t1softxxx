<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_game_description_20230803 extends CI_Migration {
    private $tableName = 'game_description';

    public function up() {
        $fields = [
            'rtp' => [
                'type' => 'VARCHAR',
                'constraint' => '11',
                'null' => true,
            ],
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            if (!$this->db->field_exists('rtp', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('rtp', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'rtp');
            }
        }
    }
}
